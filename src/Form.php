<?php declare(strict_types = 1);

namespace Fsubmit;

use voku\helper\HtmlDomParser;

/**
 * Class Form
 *
 * Submits HTML forms like browser does it:
 * unused fields to submit are automatically added from the form.
 *
 */
final class Form
{
    private const DEFAULT_CURL_OPTS = [
        CURLOPT_RETURNTRANSFER => 1, // Return the transfer as a string instead of printing it to output.
        CURLOPT_FOLLOWLOCATION => 1, // Follow HTTP 3xx redirects.
        CURLOPT_FAILONERROR => 1,
    ];

    private const SUBMITTABLE_ELEMENTS = [
        'button',
        'input',
        'select',
        'textarea',
    ];

    private string $action;
    private string $method;
    private string $enctype;
    private array $params;

    private function __construct(
        string $action,
        string $method,
        string $enctype,
        array $params
    ) {
        $this->action = $action;
        $this->method = $method;
        $this->enctype = $enctype;
        $this->params = $params;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getEnctype(): string
    {
        return $this->enctype;
    }

    public function setEnctype(string $enctype): void
    {
        $this->enctype = $enctype;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @throws FsubmitException
     */
    public static function fromUrl(
        string $url,
        array $id = ['type' => 'index', 'value' => 0],
        array $curlOpts = []
    ): Form {

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new FsubmitException("URL $url is not valid.");
        }

        $response = self::httpRequest($url, $curlOpts);
        $form = self::parse($response['content'], $id, $response['headers']['url']);

        return new self(
            $form['action'],
            $form['method'],
            $form['enctype'],
            $form['params']
        );
    }

    /**
     * @throws FsubmitException
     */
    public static function fromHtml(
        string $html,
        array $id = ['type' => 'index', 'value' => 0]
    ): Form {
        $form = self::parse($html, $id);

        return new self(
            $form['action'],
            $form['method'],
            $form['enctype'],
            $form['params']
        );
    }

    /**
     * @throws FsubmitException
     */
    public function submit(array $curlOpts = []): array
    {
        $params = 'application/x-www-form-urlencoded' === $this->enctype ?
            http_build_query($this->params) :
            $this->params;

        $action = 'GET' === $this->method ?
            $this->action . '?' . $params :
            $this->action;

        if ('POST' === $this->method) {
            $curlOpts[CURLOPT_POST] = TRUE;
            $curlOpts[CURLOPT_POSTFIELDS] = $params;
        }

        return self::httpRequest($action, $curlOpts);
    }

    /**
     * @throws FsubmitException
     */
    private static function httpRequest(string $url, array $curlOpts): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new FsubmitException("Cannot send HTTP request, URL $url is not valid.");
        }

        $curlHandle = curl_init($url);
        curl_setopt_array($curlHandle, $curlOpts + self::DEFAULT_CURL_OPTS);
        $response = curl_exec($curlHandle);
        $errorNumber = curl_errno($curlHandle);
        $errorMessage = curl_error($curlHandle);
        $headers = curl_getinfo($curlHandle);
        curl_close($curlHandle);

        if ($errorMessage) {
            throw new FsubmitException(sprintf(
                'Cannot send HTTP request, cUrl returned error: %s cUrl error number: %d.',
                $errorMessage,
                $errorNumber
            ));
        }

        return [
            'content' => $response,
            'headers' => $headers,
        ];
    }

    /**
     * @throws FsubmitException
     */
    private static function parse(string $html, array $id, string $url = NULL): array
    {
        // TODO: remove simple_html_dom dependency and replace the parser with https://www.php.net/manual/en/domdocument.loadhtml.php
        $dom = self::loadDom($html);
        $form = self::getForm($dom, $id);
        $action = self::parseAction($form, $url);
        $method = $form->method ?? 'GET';
        $enctype = $form->enctype ?? 'application/x-www-form-urlencoded';
        $params = self::parseParams($form);

        return [
            'action' => $action,
            'method' => $method,
            'enctype' => $enctype,
            'params' => $params,
        ];
    }

    /**
     * @throws FsubmitException
     */
    private static function loadDom(string $html): HtmlDomParser
    {
        $dom = HtmlDomParser::str_get_html($html);

        if (!$dom) {
            throw new FsubmitException('Cannot load DOM, cannot parse HTML string to object.');
        }

        return $dom;
    }

    /**
     * @throws FsubmitException
     */
    private static function getForm(HtmlDomParser $dom, array $id)
    {
        if ('index' === $id['type']) {
            $form = $dom->find('form', $id['value']);
        } elseif ('id' === $id['type']) {
            $form = $dom->find('form[id='.$id['value'].']', 0);
        } elseif ('name' === $id['type']) {
            $form = $dom->find('form[name='.$id['value'].']', 0);
        }

        if (!isset($form)) {
            throw new FsubmitException('Cannot get form from DOM, no form found in the provided HTML.');
        }

        return $form;
    }

    /**
     * @throws FsubmitException
     */
    private static function parseAction($form, string $url): string
    {
        $action = $form->action ?? '';

        if ('' === $action) {
            return $url;
        }

        if (!$url && !filter_var($action, FILTER_VALIDATE_URL)) {
            throw new FsubmitException('Cannot set form action, URL is not provided.');
        }

        return filter_var($action, FILTER_VALIDATE_URL) ? $action : self::actionToUrl($action, $url);
    }

    private static function parseParams($form): array
    {
        $params = [];
        $elements = [];

        $elements = isset($form->id) && '' !== $form->id ?
            array_merge(
                array_map(static fn(string $element) => $form->find($element), self::SUBMITTABLE_ELEMENTS),
                array_map(static fn(string $element) => $form->find($element.'[form='.$form->id.']'), self::SUBMITTABLE_ELEMENTS)
            ) :
            array_map(static fn(string $element) => $form->find($element), self::SUBMITTABLE_ELEMENTS);

        foreach ($elements as $element) {
            if (
                $element->disabled ||
                !isset($element->name, $element->tag, $element->type) ||
                '' === $element->name ||
                !$element->tag ||
                !$element->type
            ) {
                continue;
            }

            $tag = $element->tag;
            $type = $element->type;
            $name = $element->name;
            $value = $element->value ?? '';

            if (
                'input' === $tag &&
                ('checkbox' === $type || 'radio' === $type)
            ) {
                $params[$name] = '' !== $value ? $value : 'on';
            }

            if (
                'input' === $tag &&
                'button' !== $type &&
                'image' !== $type &&
                'reset' !== $type &&
                'submit' !== $type
            ) {
                $params[$name] = $value;
            }

            if ('textarea' === $tag) {
                $params[$name] = $element->innertext;
            }

            if ('select' === $tag) {
                $options = $element->find('option');
                $selected = NULL;
                $first = NULL;

                foreach ($options as $option) {
                    if (
                        $option->selected &&
                        !$option->disabled &&
                        !$option->parent()->disabled
                    ) {
                        $selected = $option->value ?? $option->innertext;
                        break;
                    }

                    if (
                        !isset($first) &&
                        !$option->disabled &&
                        !$option->parent()->disabled
                    ) {
                        $first = $option->value ?? $option->innertext;
                    }
                }

                $value = $selected ?? $first;
                $params[$name] = $value ?? '';
            }
        }

        return $params;
    }

    /**
     * @throws FsubmitException
     */
    private static function actionToUrl(string $action, string $url): string
    {
        if (filter_var($action, FILTER_VALIDATE_URL)) {
            return $action;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new FsubmitException("URL $url is not valid.");
        }

        $parsed = parse_url($url);
        $path = !isset($parsed['path']) || 0 === strpos($action, '/') ? $action : $parsed['path'].'/'.$action;

        return $parsed['scheme'].'://'.$parsed['host'].$path;
    }
}

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

    public static function fromUrl(
        string $url,
        array $id = ['type' => 'index', 'value' => 0],
        array $curlOpts = []
    ): Form {
        $response = self::httpRequest($url, $curlOpts);
        $form = self::parse($response['content'], $id, $response['headers']['url']);

        return new self(
            $form['action'],
            $form['method'],
            $form['enctype'],
            $form['params']
        );
    }

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
     * @param array $curlOpts
     * @return array
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
     * @param string $url
     * @param array $curlOpts
     * @return array
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function httpRequest(string $url, array $curlOpts): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Cannot send HTTP requiest, URL is not valid.');
        }

        $curlHandle = curl_init($url);
        curl_setopt_array($curlHandle, $curlOpts + self::DEFAULT_CURL_OPTS);
        $response = curl_exec($curlHandle);
        $errorNumber = curl_errno($curlHandle);
        $errorMessage = curl_error($curlHandle);
        $headers = curl_getinfo($curlHandle);
        curl_close($curlHandle);

        if ($errorMessage) {
            throw new RuntimeException(
                "Cannot send HTTP requiest, cUrl returned error: $errorMessage cUrl error number: $errorNumber.",
            );
        }

        return [
            'content' => $response,
            'headers' => $headers,
        ];
    }

    /**
     * @param string $html
     * @param array $id
     * @param string|null $url
     * @return array
     */
    private static function parse(string $html, array $id, string $url = NULL): array
    {
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

    private static function loadDom(string $html): HtmlDomParser
    {
        $dom = HtmlDomParser::str_get_html($html);

        if (!$dom) {
            throw new RuntimeException('Cannot load DOM, cannot parse HTML string to object.');
        }

        return $dom;
    }

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
            throw new RuntimeException('Cannot get form from DOM, no form found in the provided HTML.');
        }

        return $form;
    }

    private static function parseAction($form, string $url): string
    {
        $action = $form->action ?? '';

        if ('' === $action) {
            return $url;
        }

        if (!$url && !filter_var($action, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Cannot set form action, URL is not provided.');
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

    private static function actionToUrl(string $action, string $url): string
    {
        if (filter_var($action, FILTER_VALIDATE_URL)) {
            return $action;
        }

        $parsed = parse_url($url);
        unset($parsed['query'], $parsed['fragment']);

        if (0 === strpos($url, '/')) {
            $parsed['query'] = $action;
        }

        $parsed['query'] .= $action;

        return implode('', $parsed);
    }
}

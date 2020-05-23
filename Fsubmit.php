<?php declare(strict_types=1);

/**
 * Class Fsubmit
 */
class Fsubmit
{

    // A URL to download HTML page from.
    // In case 'action' is a relative link, the URL will be used as a base to transform the relative URL to an absolute one.
    // For example: $url = http://ya.ru/search.htm, <form action="gofigure.html" => $action = http://ya.ru/gofigure.html.
    public $url = NULL;

    // An HTML page to be used instead of the one located at $url.
    public $html = NULL;

    // An ID to get the form by (index/id/name).
    // Use one (and one only) of these variables.
    // If none set, the first form on the page will be used (index=0).
    public $index = NULL;
    public $id = NULL;
    public $name = NULL;

    // Form data to submit (name=>value).
    // If a parameter set here did not exist in the form, it will be added to it.
    // If custom submit name and value - provide them here.
    public $params = array();

    // cURL options (http://php.net/manual/en/function.curl-setopt.php).
    // Do not provide form data here, it will be deprecated - provide form data in $params.
    public $curlOpts = array();

    // Default cURL options.
    protected $defaultCurlOpts = array(
        CURLOPT_RETURNTRANSFER => 1, // Return the transfer as a string instead of printing it to output.
        CURLOPT_FOLLOWLOCATION => 1 // Follow HTTP 3xx redirects.
    );

    /**
     * Transforms the given URL from relative to an absolute one.
     * Throws an error if the resulting transformed URL is not a valid URL.
     * @param $url
     * @return string
     * @throws Exception
     */
    private function urlToAbs($url): string
    {
        $url = phpUri::parse(
            strstr($_SERVER['SERVER_PROTOCOL'], '/', true)
            . '://'
            . $_SERVER['SERVER_NAME']
            . htmlentities($_SERVER['REQUEST_URI']) // htmlentities is required to protect from XSS attacks.
        )->join($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) { // Is it an absolute link now? Throw an exception if not.
            Throw new Exception("fsubmit: URL provided in url variable ($url) is not a valid URL.");
        }

        return $url;
    }

    /**
     * Gets HTML page from $url or, if it is not set, from $this->url.
     * Returns array:
     *    Array (
     *        'content' - HTML page returned by server.
     *        'header' - HTTP header.
     *    )
     * @param string|NULL $url
     * @return array
     * @throws Exception
     */
    public function getPage(string $url = NULL): array
    {
        // Make sure we have a URL to work with.
        if (isset($url)) {
            $this->url = $url;
        } elseif (isset($this->url)) {
            $url = $this->url;
        } else {
            throw new Exception ('fsubmit: getPage(): URL is not set, cannot download page from nowhere.');
        }

        // Make sure the URL is an absolute URL (cUrl cannot work with relative ones).
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = $this->urlToAbs($url);
        }

        // Merge cURL options, choosing user defined options over default ones.
        $this->curlOpts = $this->curlOpts + $this->defaultCurlOpts;

        // Get HTML page.
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->curlOpts);
        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        if ($errno) {
            throw new Exception ("fsubmit: cURL error: $errmsg.");
        }

        // Return result.
        return array(
            'content' => $content,
            'header' => $header
        );
    }

    /**
     * Gets HTML form from $this->html, or, if it is not set, from $this->url and submits it.
     * Depends on getPage().
     * Returns array:
     *    Array (
     *        'content' - HTML page returned by server in response of form submit.
     *        'header' - HTTP header.
     *    )
     * @return array
     * @throws Exception
     */
    public function submit(): array
    {

        // ========================================================================================
        // Check input.
        // ========================================================================================

        // At least one of form sources (URL/HTML) must be provided.
        if (!(isset($this->url) or isset($this->html))) {
            throw new Exception ('fsubmit: Neither a URL nor an HTML page are provided to get the form from.');
        }

        // Count how many form identifiers (name, index, ID) are provided, to make sue we have not more than one.
        $form_id_count = isset($this->index) + isset($this->id) + isset($this->name);

        if ($form_id_count == 0) {
            $this->index = 0; // If no identifier provided, use the first HTML form on the page.
        } elseif ($form_id_count != 1) {
            throw new Exception ('fsubmit: More than one of HTML form identifiers (index, name, ID) is provided, there must be one and one only.');
        }

        // Make sure the URL provided is an absolute URL (cUrl cannot work with relative ones).
        if (isset($this->url)) {
            if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
                $url = $this->urlToAbs($this->url);
            } else {
                $url = $this->url;
            }
        }

        // ========================================================================================
        // Get HTML.
        // ========================================================================================

        // If HTML string is not set, get the form from URL.

        if (!isset($this->html)) {
            $this->html = $this->getPage($url)['content'];
        }

        // ========================================================================================
        // Parse HTML to DOM.
        // ========================================================================================

        $dom = str_get_html($this->html);

        // Get form by index.
        if (isset($this->index)) {
            $form = $dom->find('form', $this->index);
        } // Get form by id.
        elseif (isset($this->id)) {
            $form = $dom->find('form[id=' . $this->id . ']', 0);
        } // Get form by name.
        elseif (isset($this->name)) {
            $form = $dom->find('form[name=' . $this->name . ']', 0);
        }

        // Make sure we found a form in the HTML.
        if (!isset($form)) {
            throw new Exception ("fsubmit: No form found in the provided HTML: $this->html.");
        }

        // Get form's method.
        $method = 'GET'; // If method attribute is not set, default method is GET.

        if (isset($form->method)) {
            $method = strtoupper($form->method);
        }

        // Get form's enctype.
        $enctype = 'application/x-www-form-urlencoded'; // If enctype attribute is not set, default enctype is application/x-www-form-urlencoded.

        if (isset($form->enctype)) {
            if ($method != 'GET') { // GET supports only one enctype - application/x-www-form-urlencoded.
                $enctype = ($form->enctype);
            }
        }

        // Get form's action.
        $action = isset($form->action) ? $form->action : NULL;

        if (isset($action)) {
            // If form's "action" attribute exists, lets see if its value is a relative link.
            if (!filter_var($action, FILTER_VALIDATE_URL)) {
                // Action is a relative link, convert it to an absolute link, using form's URL as base.
                if (isset($url)) {
                    $action = phpUri::parse($url)->join($action);
                } else {
                    throw new Exception ("fsubmit: Form's 'action' is a relative link AND no URL is provided in '->url' variable, don't know where to submit the form to.");
                }
            }
        } else {
            // If "action" attribute does not exist, the form must be submitted to where it was downloaded from.
            if (isset($url)) {
                $action = $url;
            } else {
                throw new Exception ("fsubmit: Form's 'action' is empty AND no URL is provided in '->url' variable, don't know where to submit the form to.");
            }
        }

        // Get form's data.
        $parsed_params = array();

        // It is important for selectors in "find" to be in a single line, without tabs or whitespaces after commas,
        // otherwise it breaks simplehtmldom's functionality.
        $form_elements = $form->find('button,input,select,textarea');

        if (isset($form->id)) {
            // To support HTML5 standard, we must make sure that if the form has an "id" attribute,
            // we find all the form's elements on the page, that have a "form" attribute with the form's id as a value.
            // It is important for selectors in "find" to be in a single line, without tabs or whitespaces after commas,
            // otherwise it breaks simplehtmldom's functionality.
            $form_elements = array_merge(
                $form_elements,
                $dom->find("button[form=$form->id],input[form=$form->id],select[form=$form->id],textarea[form=$form->id]")
            );
        }

        foreach ($form_elements as $element) {

            $tag = isset($element->tag) ? strtolower($element->tag) : NULL;
            $type = isset($element->type) ? strtolower($element->type) : NULL;

            // Ignore disabled elements.
            if ($element->disabled) {
                continue;
            }

            // Ignore elements without a name.
            if (!isset($element->name) or empty ($element->name)) {
                continue;
            }

            // input
            if ($tag == 'input') {
                if ($type == 'checkbox' or $type == 'radio') {
                    if ($element->checked) {
                        if ($element->value) {
                            $parsed_params[$element->name] = $element->value;
                        } else {
                            $parsed_params[$element->name] = 'on'; // Default value for a checked checkbox is "on";
                        }
                    }
                } elseif ($type != 'button' and $type != 'image' and $type != 'reset' and $type != 'submit') {
                    if ($element->value) {
                        $parsed_params[$element->name] = $element->value;
                    } else {
                        $parsed_params[$element->name] = '';
                    }
                }
            }

            // textarea
            if ($tag == 'textarea') {
                $parsed_params[$element->name] = $element->innertext;
            }

            // select
            if ($tag == 'select') {

                $selected = $element->find('option[selected]', 0); // Get the first "option" with "selected" attribute present.

                if (isset($selected)) { // Use this "option", unless it has "disabled" attribute.
                    if (!($selected->disabled or $selected->parent()->disabled)) {
                        $option = $selected;
                    }
                } else { // If no "option" has "selected" attribute, use first "option" in the list.
                    foreach ($element->find('option') as $first) {
                        if ($first->disabled or $first->parent()->disabled) {
                            continue;
                        } else {
                            $option = $first;
                            break;
                        }
                    }
                }

                if (isset($option)) {
                    if (isset($option->value)) { // If "value" attribute exists, use its value for parameter's value: "<option value="pa1">Param1</option>" => "pa1".
                        $parsed_params[$element->name] = $option->value;
                    } else { // If no "value" attribute, the option's inner text must be used as a parameter's value: "<option>Param1</option>" => "Param1".
                        $parsed_params[$element->name] = $option->innertext;
                    }
                }

            }
        }

        // ========================================================================================
        // Prepare form for submitting.
        // ========================================================================================

        // Merge form data.
        $params = $this->params + $parsed_params;

        // Encode form data.
        if ($enctype == 'application/x-www-form-urlencoded') {
            $params = http_build_query($params);
        }

        // Merge cURL options.
        $this->curlOpts = $this->curlOpts + $this->defaultCurlOpts;

        // Adjust cURL options according to method.
        switch ($method) {
            case 'GET':
                $action = $action . '?' . $params;
                break;
            case 'POST':
                $this->curlOpts[CURLOPT_POST] = TRUE;
                $this->curlOpts[CURLOPT_POSTFIELDS] = $params;
                break;
        }

        // ========================================================================================
        // Submit.
        // ========================================================================================

        return $this->getPage($action);
    }
}

# fsubmit

Have you ever tried to submit an HTML form with cUrl? You have to clearly state all the fields you submit and what values they have. 

In real life, we most often only want to fill in one or two fields without even thinking about what other fields are.

If you hardcode the other fields' values into your cUrl request, what if the form changes over time? You code will be broken. 

To keep you code adoptable to the changes of the form, you will have to download the form as is first, parse its fields and values, change/add values to the right fields and submit it with cUrl. 

It causes a lot of questions if you do not know how HTML forms work. For example, if there is a select tag with several options, which one will be submitted as the value for the field if none is selected? What if the option tag has no value attribute?

An Internet browser does the job for us when we submit a form. We do not have to bother about hidden fields or any other fields at all. The library provides the same functionality for PHP.

## Requirements

PHP 7.4 and later.

## Composer

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require randomsymbols/fsubmit
```

To use the bindings, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once('vendor/autoload.php');
```

## Dependencies

The bindings require the following extensions in order to work properly:

-   [`curl`](https://secure.php.net/manual/en/book.curl.php)
-   [`openssl`](https://www.php.net/manual/en/openssl.installation.php)
-   [`PHP Simple HTML DOM Parser`](https://github.com/voku/simple_html_dom)

If you use Composer, these dependencies should be handled automatically. If you install manually, you'll want to make sure these extensions are available.

## Getting Started

```php
use Fsubmit\Form;

$form = Form::fromUrl('https://www.google.com');
$form->setParams(['q' => 'John 3:16']);
$answer = $form->submit();
echo $answer['content'];
```

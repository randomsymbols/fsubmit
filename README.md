# fsubmit

> **This library is no longer maintained.** Use [symfony/dom-crawler](https://symfony.com/doc/current/components/dom_crawler.html)
> and [symfony/browser-kit](https://symfony.com/doc/current/components/browser_kit.html) instead.
> They work standalone, without the Symfony framework, and parse and submit HTML forms the way a browser does.

## Migrating

Fetch the page, fill in the fields you care about and submit the form; everything else is taken from the form as is:

```php
use Symfony\Component\BrowserKit\HttpBrowser;

$browser = new HttpBrowser();
$crawler = $browser->request('GET', 'https://www.google.com');
$form = $crawler->filter('form')->form(['q' => 'John 3:16']);
$browser->submit($form);
echo $browser->getResponse()->getContent();
```

```bash
composer require symfony/browser-kit symfony/http-client symfony/css-selector
```

If you prefer to send the request with your own HTTP client, use `symfony/dom-crawler` alone:

```php
use Symfony\Component\DomCrawler\Crawler;

$crawler = new Crawler($html, 'https://www.google.com');
$form = $crawler->filter('form')->form(['q' => 'John 3:16']);

$form->getMethod();    // GET
$form->getUri();       // absolute action URL, with the query string for GET forms
$form->getPhpValues(); // fields to send in the body for POST forms
```

The original documentation is kept below for reference.

---

Have you ever tried to submit an HTML form with cUrl?
You have to clearly state all the fields you submit and what values they have. 

In real life, we most often only want to fill in one or two fields without even thinking about what other fields are.

If you hardcode the other fields' values into your cUrl request, what if the form changes over time?
You code will be broken. 

To keep you code adoptable to the changes of the form, you will have to download the form as is first, 
parse its fields and values, change/add values to the right fields and submit it with cUrl. 

It causes a lot of questions if you do not know how HTML forms work. 
For example, if there is a select tag with several options, 
which one will be submitted as the value for the field if none is selected? What if the option tag has no value attribute?

An Internet browser does the job for us when we submit a form. 
We do not have to bother about hidden fields or any other fields at all. The library provides the same functionality for PHP.

## Requirements

PHP 7.4 and later.

## Composer

You can install the library via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require randomsymbols/fsubmit
```

To use the library, use composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once('vendor/autoload.php');
```

## Dependencies

The library requires the following extensions in order to work properly:

-   [`curl`](https://secure.php.net/manual/en/book.curl.php)
-   [`openssl`](https://www.php.net/manual/en/openssl.installation.php)
-   [`PHP Simple HTML DOM Parser`](https://github.com/voku/simple_html_dom)

If you use Composer, these dependencies should be handled automatically. If you install manually, make sure these extensions are available.

## Getting Started

```php
use Fsubmit\Form;

$form = Form::fromUrl('https://www.google.com');
$form->setParams(['q' => 'John 3:16']);
$answer = $form->submit();
echo $answer['content'];
```

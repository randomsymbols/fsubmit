fsubmit
==========================

Have you ever tried to submit an HTML form with cUrl? You have to clearly state all the fields you submit and what values they have. 

But in real life, we most often only want to fill in one or two fields without even thinking about what other fields are.

If you hardcode the other filds' values into your cUrl request, what if the form changes over time? You code will be broken. 

To keep you code adoptable to the changes of the form, you will have to download the form as is first, parse its fileds and values, change/add values to the right fields and submit it with cUrl. 

But it causes a lot of questions if you do not know how HTML forms work. For example, if there is a select tag with several options, which one will be submitted as the value for the field if none is selected? And what if the option tag has no value attribute?

An Internet browser does the job for us when we submit a form. We do not have to borther about hidden fields or any other fields at all. The library is created to provide the same functionality for PHP.

Install
-------

With [composer](https://en.wikipedia.org/wiki/Composer_(software)) (the first 2 lines are reuired to fix simplehtmldom composer installation):
```composer
composer config minimum-stability rc
composer config prefer-stable true
composer require randomsymbols/fsubmit
```

If installed manually, make sure you install the dependencies also:
1. [PHP Simple HTML DOM Parser](https://simplehtmldom.sourceforge.io/)
2. [phpUri](https://github.com/monkeysuffrage/phpuri)

Usage
-----

```php
require 'vendor/simplehtmldom/simplehtmldom/simple_html_dom.php'; // Required to fix simplehtmldom composer autoload.

$form = new Fsubmit();
$form->url = 'https://www.google.com'; // Download the form from this page.
$form->params = ['q' => 'John 3:16']; // Set (or add if it doesn't exist) a parameter in the downloaded form.
$answer = $form->submit();
echo $answer['content'];
```

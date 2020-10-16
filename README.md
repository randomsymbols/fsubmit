fsubmit
==========================

Have you ever tried to submit an HTML form with cUrl? You have to clearly state all the fields you submit and what values they have. 

In real life, we most often only want to fill in one or two fields without even thinking about what other fields are.

If you hardcode the other fields' values into your cUrl request, what if the form changes over time? You code will be broken. 

To keep you code adoptable to the changes of the form, you will have to download the form as is first, parse its fields and values, change/add values to the right fields and submit it with cUrl. 

It causes a lot of questions if you do not know how HTML forms work. For example, if there is a select tag with several options, which one will be submitted as the value for the field if none is selected? What if the option tag has no value attribute?

An Internet browser does the job for us when we submit a form. We do not have to bother about hidden fields or any other fields at all. The library provides the same functionality for PHP.

Install
-------

With [composer](https://en.wikipedia.org/wiki/Composer_(software)):
```composer
composer require randomsymbols/fsubmit
```

If installed manually, make sure you install the dependencies also:
1. [PHP Simple HTML DOM Parser](https://github.com/voku/simple_html_dom)
2. [phpUri](https://github.com/monkeysuffrage/phpuri)

Usage
-----

```php
use Fsubmit\Form;

$form = Form::fromUrl('https://www.google.com');
$form->setParams(['q' => 'John 3:16']);
$answer = $form->submit();
echo $answer['content'];
```

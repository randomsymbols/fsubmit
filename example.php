<?php declare(strict_types = 1);

use Fsubmit\Form;

$form = Form::fromUrl('https://www.google.com');
$form->setParams(['q' => 'John 3:16']);
$answer = $form->submit();
echo $answer['content'];

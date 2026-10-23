<?php declare(strict_types = 1);

namespace Fsubmit\Tests;

use Fsubmit\Form;
use Fsubmit\FsubmitException;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private const HTML = '<form action="https://example.com/submit" method="post"><input name="q" value="v"></form>';

    public function testFromHtmlReadsTheForm(): void
    {
        $form = Form::fromHtml(self::HTML);

        self::assertSame('https://example.com/submit', $form->getAction());
        self::assertSame('POST', $form->getMethod());
        self::assertSame('application/x-www-form-urlencoded', $form->getEnctype());
        self::assertSame(['q' => 'v'], $form->getParams());
    }

    public function testSettersChangeTheForm(): void
    {
        $form = Form::fromHtml(self::HTML);

        $form->setAction('https://example.org/other');
        $form->setMethod('GET');
        $form->setEnctype('multipart/form-data');

        self::assertSame('https://example.org/other', $form->getAction());
        self::assertSame('GET', $form->getMethod());
        self::assertSame('multipart/form-data', $form->getEnctype());
    }

    public function testSetParamsKeepsTheFormFieldsNotBeingSet(): void
    {
        $form = Form::fromHtml(
            '<form action="https://example.com/"><input name="token" value="t"><input name="q"></form>'
        );

        $form->setParams(['q' => 'search']);

        self::assertSame(['token' => 't', 'q' => 'search'], $form->getParams());
    }

    public function testFromHtmlWithoutFormThrows(): void
    {
        $this->expectException(FsubmitException::class);

        Form::fromHtml('<div>no form here</div>');
    }

    public function testFromHtmlWithMissingFormIndexThrows(): void
    {
        $this->expectException(FsubmitException::class);

        Form::fromHtml(self::HTML, ['type' => 'index', 'value' => 1]);
    }

    public function testFromHtmlWithMissingFormIdThrows(): void
    {
        $this->expectException(FsubmitException::class);

        Form::fromHtml(self::HTML, ['type' => 'id', 'value' => 'missing']);
    }

    public function testFromHtmlWithUnknownSelectorTypeThrows(): void
    {
        $this->expectException(FsubmitException::class);

        Form::fromHtml(self::HTML, ['type' => 'class', 'value' => 'x']);
    }

    public function testFromHtmlWithRelativeActionAndNoDocumentUrlThrows(): void
    {
        $this->expectException(FsubmitException::class);

        Form::fromHtml('<form action="/submit"></form>');
    }

    public function testFromUrlWithInvalidUrlThrows(): void
    {
        $this->expectException(FsubmitException::class);

        Form::fromUrl('not a url');
    }
}

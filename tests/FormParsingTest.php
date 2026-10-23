<?php declare(strict_types = 1);

namespace Fsubmit\Tests;

use Fsubmit\Form;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Checks that forms are parsed the way a browser would submit them.
 *
 * The cases live in Fixtures/forms.php. parseForm() is the only place
 * that knows the library API, so the fixtures survive API changes.
 */
class FormParsingTest extends TestCase
{
    private const DOCUMENT_URL = 'https://example.com/dir/page.html';

    public function formProvider(): array
    {
        $cases = [];

        foreach (require __DIR__ . '/Fixtures/forms.php' as $name => $case) {
            $cases[$name] = [$case];
        }

        return $cases;
    }

    /**
     * @dataProvider formProvider
     */
    public function testFormIsParsedLikeBrowser(array $case): void
    {
        $actual = $this->parseForm(
            $case['html'],
            $case['form'] ?? ['index' => 0],
            $case['url'] ?? self::DOCUMENT_URL
        );

        foreach ($case['expected'] as $key => $expected) {
            self::assertSame($expected, $actual[$key], "Unexpected $key.");
        }
    }

    /**
     * @return array{action: string, method: string, enctype: string, fields: list<array{0: string, 1: string}>}
     */
    private function parseForm(string $html, array $selector, string $url): array
    {
        $parse = new ReflectionMethod(Form::class, 'parse');
        $parse->setAccessible(true);
        $form = $parse->invoke(null, $html, ['type' => key($selector), 'value' => current($selector)], $url);

        $fields = [];

        foreach ($form['params'] as $name => $value) {
            $fields[] = [(string) $name, (string) $value];
        }

        return [
            'action' => $form['action'],
            'method' => $form['method'],
            'enctype' => $form['enctype'],
            'fields' => $fields,
        ];
    }
}

<?php declare(strict_types = 1);

/**
 * HTML form fixtures and what a browser would submit for each of them.
 *
 * Expected values follow the HTML Living Standard "constructing the entry list"
 * and "form submission" algorithms, as implemented by current browsers.
 *
 * Each case:
 *   html      - the document
 *   form      - optional form selector: ['index' => int] | ['id' => string] | ['name' => string], default first form
 *   url       - optional URL the document was loaded from, default self::DOCUMENT_URL in FormParsingTest
 *   expected  - only the listed keys are asserted:
 *                 action  - absolute action URL
 *                 method  - GET or POST
 *                 enctype - application/x-www-form-urlencoded | multipart/form-data | text/plain
 *                 fields  - ordered list of [name, value] pairs, duplicates allowed
 */

return [

    // ----- Text-like inputs -------------------------------------------------

    'text input submits its value' => [
        'html' => '<form><input type="text" name="q" value="hello"></form>',
        'expected' => ['fields' => [['q', 'hello']]],
    ],
    'input without type is a text input' => [
        'html' => '<form><input name="q" value="hello"></form>',
        'expected' => ['fields' => [['q', 'hello']]],
    ],
    'input with unknown type is a text input' => [
        'html' => '<form><input type="foo" name="q" value="hello"></form>',
        'expected' => ['fields' => [['q', 'hello']]],
    ],
    'input without value submits empty string' => [
        'html' => '<form><input type="text" name="q"></form>',
        'expected' => ['fields' => [['q', '']]],
    ],
    'hidden input submits its value' => [
        'html' => '<form><input type="hidden" name="token" value="abc123"></form>',
        'expected' => ['fields' => [['token', 'abc123']]],
    ],
    'html5 input types submit their value' => [
        'html' => '<form>'
            . '<input type="email" name="email" value="a@b.c">'
            . '<input type="number" name="n" value="5">'
            . '<input type="password" name="p" value="secret">'
            . '<input type="search" name="s" value="x">'
            . '<input type="date" name="d" value="2020-01-01">'
            . '</form>',
        'expected' => ['fields' => [
            ['email', 'a@b.c'],
            ['n', '5'],
            ['p', 'secret'],
            ['s', 'x'],
            ['d', '2020-01-01'],
        ]],
    ],
    'html entities in value are decoded' => [
        'html' => '<form><input name="q" value="a &amp; b &quot;c&quot; &lt;d&gt;"></form>',
        'expected' => ['fields' => [['q', 'a & b "c" <d>']]],
    ],
    'non-ascii value is preserved' => [
        'html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
            . '<form><input name="q" value="Привет, 世界"></form></body></html>',
        'expected' => ['fields' => [['q', 'Привет, 世界']]],
    ],
    'readonly input is submitted' => [
        'html' => '<form><input name="q" value="v" readonly></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'file input without a file submits an empty value' => [
        'html' => '<form><input type="file" name="upload"></form>',
        'expected' => ['fields' => [['upload', '']]],
    ],

    // ----- Fields that are skipped ------------------------------------------

    'input without name is skipped' => [
        'html' => '<form><input value="x"><input name="q" value="y"></form>',
        'expected' => ['fields' => [['q', 'y']]],
    ],
    'input with empty name is skipped' => [
        'html' => '<form><input name="" value="x"><input name="q" value="y"></form>',
        'expected' => ['fields' => [['q', 'y']]],
    ],
    'disabled input is skipped' => [
        'html' => '<form><input name="a" value="1" disabled><input name="b" value="2"></form>',
        'expected' => ['fields' => [['b', '2']]],
    ],
    'submit, reset, button and image inputs are skipped' => [
        'html' => '<form>'
            . '<input type="submit" name="s" value="Go">'
            . '<input type="reset" name="r" value="Reset">'
            . '<input type="button" name="b" value="Btn">'
            . '<input type="image" name="i" src="x.png">'
            . '<input name="q" value="v">'
            . '</form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'button elements are skipped' => [
        'html' => '<form><button name="b" value="1">Go</button><button type="button" name="c">C</button>'
            . '<input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'fields inside a disabled fieldset are skipped' => [
        'html' => '<form><fieldset disabled><input name="a" value="1"><select name="s"><option>x</option></select>'
            . '<textarea name="t">x</textarea></fieldset><input name="b" value="2"></form>',
        'expected' => ['fields' => [['b', '2']]],
    ],
    'fields in the first legend of a disabled fieldset are submitted' => [
        'html' => '<form><fieldset disabled><legend><input name="a" value="1"></legend>'
            . '<input name="b" value="2"></fieldset></form>',
        'expected' => ['fields' => [['a', '1']]],
    ],
    'fields outside of any form are skipped' => [
        'html' => '<input name="outside" value="x"><form><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'object and output elements are not submitted' => [
        'html' => '<form><output name="o">5</output><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],

    // ----- Checkboxes and radios ----------------------------------------------

    'unchecked checkbox is skipped' => [
        'html' => '<form><input type="checkbox" name="c" value="1"><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'checked checkbox submits its value' => [
        'html' => '<form><input type="checkbox" name="c" value="yes" checked></form>',
        'expected' => ['fields' => [['c', 'yes']]],
    ],
    'checked checkbox without value submits "on"' => [
        'html' => '<form><input type="checkbox" name="c" checked></form>',
        'expected' => ['fields' => [['c', 'on']]],
    ],
    'checked checkbox with empty value submits empty string' => [
        'html' => '<form><input type="checkbox" name="c" value="" checked></form>',
        'expected' => ['fields' => [['c', '']]],
    ],
    'several checked checkboxes with the same name are all submitted' => [
        'html' => '<form>'
            . '<input type="checkbox" name="tags[]" value="a" checked>'
            . '<input type="checkbox" name="tags[]" value="b">'
            . '<input type="checkbox" name="tags[]" value="c" checked>'
            . '</form>',
        'expected' => ['fields' => [['tags[]', 'a'], ['tags[]', 'c']]],
    ],
    'only the checked radio is submitted' => [
        'html' => '<form>'
            . '<input type="radio" name="r" value="a">'
            . '<input type="radio" name="r" value="b" checked>'
            . '<input type="radio" name="r" value="c">'
            . '</form>',
        'expected' => ['fields' => [['r', 'b']]],
    ],
    'radio group without a checked radio is skipped' => [
        'html' => '<form><input type="radio" name="r" value="a"><input type="radio" name="r" value="b">'
            . '<input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'when several radios of one group are checked the last one wins' => [
        'html' => '<form><input type="radio" name="r" value="a" checked><input type="radio" name="r" value="b" checked></form>',
        'expected' => ['fields' => [['r', 'b']]],
    ],
    'checked radio without value submits "on"' => [
        'html' => '<form><input type="radio" name="r" checked></form>',
        'expected' => ['fields' => [['r', 'on']]],
    ],
    'disabled checked checkbox is skipped' => [
        'html' => '<form><input type="checkbox" name="c" value="1" checked disabled><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],

    // ----- Textarea -----------------------------------------------------------

    'textarea submits its content' => [
        'html' => '<form><textarea name="t">Hello world</textarea></form>',
        'expected' => ['fields' => [['t', 'Hello world']]],
    ],
    'textarea content entities are decoded' => [
        'html' => '<form><textarea name="t">a &amp; b &lt;c&gt;</textarea></form>',
        'expected' => ['fields' => [['t', 'a & b <c>']]],
    ],
    'textarea content is not parsed as html' => [
        'html' => '<form><textarea name="t"><b>bold</b></textarea></form>',
        'expected' => ['fields' => [['t', '<b>bold</b>']]],
    ],
    'textarea leading newline is dropped' => [
        'html' => "<form><textarea name=\"t\">\nline1\nline2</textarea></form>",
        'expected' => ['fields' => [['t', "line1\nline2"]]],
    ],
    'empty textarea submits empty string' => [
        'html' => '<form><textarea name="t"></textarea></form>',
        'expected' => ['fields' => [['t', '']]],
    ],
    'disabled textarea is skipped' => [
        'html' => '<form><textarea name="t" disabled>x</textarea><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],

    // ----- Select -------------------------------------------------------------

    'select submits the selected option' => [
        'html' => '<form><select name="s"><option value="a">A</option><option value="b" selected>B</option></select></form>',
        'expected' => ['fields' => [['s', 'b']]],
    ],
    'select without selected option submits the first option' => [
        'html' => '<form><select name="s"><option value="a">A</option><option value="b">B</option></select></form>',
        'expected' => ['fields' => [['s', 'a']]],
    ],
    'select without selected option skips disabled options' => [
        'html' => '<form><select name="s"><option value="a" disabled>A</option><option value="b">B</option></select></form>',
        'expected' => ['fields' => [['s', 'b']]],
    ],
    'select without selected option skips options in disabled optgroup' => [
        'html' => '<form><select name="s"><optgroup disabled><option value="a">A</option></optgroup>'
            . '<optgroup><option value="b">B</option></optgroup></select></form>',
        'expected' => ['fields' => [['s', 'b']]],
    ],
    'option without value submits its text' => [
        'html' => '<form><select name="s"><option>First</option><option selected>Second</option></select></form>',
        'expected' => ['fields' => [['s', 'Second']]],
    ],
    'option text has whitespace stripped and collapsed' => [
        'html' => "<form><select name=\"s\"><option>  Two \n  words  </option></select></form>",
        'expected' => ['fields' => [['s', 'Two words']]],
    ],
    'option with empty value submits empty string' => [
        'html' => '<form><select name="s"><option value="">Choose</option><option value="a">A</option></select></form>',
        'expected' => ['fields' => [['s', '']]],
    ],
    'when several options of a single select are selected the last one wins' => [
        'html' => '<form><select name="s"><option value="a" selected>A</option><option value="b" selected>B</option></select></form>',
        'expected' => ['fields' => [['s', 'b']]],
    ],
    'multiple select submits every selected option' => [
        'html' => '<form><select name="s[]" multiple>'
            . '<option value="a" selected>A</option><option value="b">B</option><option value="c" selected>C</option>'
            . '</select></form>',
        'expected' => ['fields' => [['s[]', 'a'], ['s[]', 'c']]],
    ],
    'multiple select without selected options is skipped' => [
        'html' => '<form><select name="s" multiple><option value="a">A</option></select><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'select with size above one and no selected option is skipped' => [
        'html' => '<form><select name="s" size="3"><option value="a">A</option></select><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'selected disabled option is not submitted' => [
        'html' => '<form><select name="s"><option value="a" selected disabled>A</option><option value="b">B</option></select>'
            . '<input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'select without options is skipped' => [
        'html' => '<form><select name="s"></select><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],
    'disabled select is skipped' => [
        'html' => '<form><select name="s" disabled><option value="a">A</option></select><input name="q" value="v"></form>',
        'expected' => ['fields' => [['q', 'v']]],
    ],

    // ----- Order, duplicates, form association ----------------------------------

    'fields are submitted in document order' => [
        'html' => '<form><input name="a" value="1"><textarea name="b">2</textarea>'
            . '<select name="c"><option>3</option></select><input name="d" value="4"></form>',
        'expected' => ['fields' => [['a', '1'], ['b', '2'], ['c', '3'], ['d', '4']]],
    ],
    'fields with the same name are all submitted' => [
        'html' => '<form><input name="a" value="1"><input name="a" value="2"></form>',
        'expected' => ['fields' => [['a', '1'], ['a', '2']]],
    ],
    'field outside the form linked by the form attribute is submitted' => [
        'html' => '<form id="f"><input name="a" value="1"></form><input name="b" value="2" form="f">',
        'form' => ['id' => 'f'],
        'expected' => ['fields' => [['a', '1'], ['b', '2']]],
    ],
    'field linked by the form attribute keeps its document position' => [
        'html' => '<input name="b" value="2" form="f"><form id="f"><input name="a" value="1"></form>',
        'form' => ['id' => 'f'],
        'expected' => ['fields' => [['b', '2'], ['a', '1']]],
    ],
    'field inside the form linked to another form is not submitted' => [
        'html' => '<form id="f"><input name="a" value="1"><input name="b" value="2" form="g"></form><form id="g"></form>',
        'form' => ['id' => 'f'],
        'expected' => ['fields' => [['a', '1']]],
    ],
    'field with the form attribute pointing nowhere is not submitted' => [
        'html' => '<form id="f"><input name="a" value="1"><input name="b" value="2" form="missing"></form>',
        'form' => ['id' => 'f'],
        'expected' => ['fields' => [['a', '1']]],
    ],
    'fields of other forms are not submitted' => [
        'html' => '<form><input name="a" value="1"></form><form><input name="b" value="2"></form>',
        'form' => ['index' => 1],
        'expected' => ['fields' => [['b', '2']]],
    ],
    'uppercase tags and attributes are handled' => [
        'html' => '<FORM METHOD="POST"><INPUT TYPE="TEXT" NAME="q" VALUE="v"><INPUT TYPE="CHECKBOX" NAME="c" CHECKED></FORM>',
        'expected' => ['method' => 'POST', 'fields' => [['q', 'v'], ['c', 'on']]],
    ],
    'fields in unclosed form are submitted' => [
        'html' => '<body><form><input name="a" value="1"><div><input name="b" value="2"></body>',
        'expected' => ['fields' => [['a', '1'], ['b', '2']]],
    ],
    'deeply nested fields are submitted' => [
        'html' => '<form><table><tr><td><div><label><input name="a" value="1"></label></div></td></tr></table></form>',
        'expected' => ['fields' => [['a', '1']]],
    ],

    // ----- Form selection -----------------------------------------------------

    'form is selected by index' => [
        'html' => '<form><input name="a" value="1"></form><form><input name="b" value="2"></form><form><input name="c" value="3"></form>',
        'form' => ['index' => 2],
        'expected' => ['fields' => [['c', '3']]],
    ],
    'form is selected by id' => [
        'html' => '<form id="one"><input name="a" value="1"></form><form id="two"><input name="b" value="2"></form>',
        'form' => ['id' => 'two'],
        'expected' => ['fields' => [['b', '2']]],
    ],
    'form is selected by name' => [
        'html' => '<form name="one"><input name="a" value="1"></form><form name="two"><input name="b" value="2"></form>',
        'form' => ['name' => 'two'],
        'expected' => ['fields' => [['b', '2']]],
    ],
    'form id with special characters is matched literally' => [
        'html' => '<form id="a]b"><input name="a" value="1"></form><form id="x"><input name="b" value="2"></form>',
        'form' => ['id' => 'a]b'],
        'expected' => ['fields' => [['a', '1']]],
    ],

    // ----- Method ---------------------------------------------------------------

    'method defaults to GET' => [
        'html' => '<form></form>',
        'expected' => ['method' => 'GET'],
    ],
    'method is case-insensitive' => [
        'html' => '<form method="post"></form>',
        'expected' => ['method' => 'POST'],
    ],
    'invalid method falls back to GET' => [
        'html' => '<form method="put"></form>',
        'expected' => ['method' => 'GET'],
    ],
    'empty method falls back to GET' => [
        'html' => '<form method=""></form>',
        'expected' => ['method' => 'GET'],
    ],

    // ----- Enctype --------------------------------------------------------------

    'enctype defaults to urlencoded' => [
        'html' => '<form method="post"></form>',
        'expected' => ['enctype' => 'application/x-www-form-urlencoded'],
    ],
    'multipart enctype is kept' => [
        'html' => '<form method="post" enctype="multipart/form-data"></form>',
        'expected' => ['enctype' => 'multipart/form-data'],
    ],
    'enctype is case-insensitive' => [
        'html' => '<form method="post" enctype="Multipart/Form-Data"></form>',
        'expected' => ['enctype' => 'multipart/form-data'],
    ],
    'text/plain enctype is kept' => [
        'html' => '<form method="post" enctype="text/plain"></form>',
        'expected' => ['enctype' => 'text/plain'],
    ],
    'invalid enctype falls back to urlencoded' => [
        'html' => '<form method="post" enctype="application/json"></form>',
        'expected' => ['enctype' => 'application/x-www-form-urlencoded'],
    ],

    // ----- Action ---------------------------------------------------------------

    'missing action is the document URL' => [
        'html' => '<form></form>',
        'url' => 'https://example.com/dir/page.html?x=1',
        'expected' => ['action' => 'https://example.com/dir/page.html?x=1'],
    ],
    'empty action is the document URL' => [
        'html' => '<form action=""></form>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://example.com/dir/page.html'],
    ],
    'absolute action is kept' => [
        'html' => '<form action="https://other.example.org/submit"></form>',
        'expected' => ['action' => 'https://other.example.org/submit'],
    ],
    'relative action is resolved against the document directory' => [
        'html' => '<form action="submit.php"></form>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://example.com/dir/submit.php'],
    ],
    'relative action is resolved when the document URL ends with a slash' => [
        'html' => '<form action="submit.php"></form>',
        'url' => 'https://example.com/dir/',
        'expected' => ['action' => 'https://example.com/dir/submit.php'],
    ],
    'relative action is resolved when the document URL has no path' => [
        'html' => '<form action="submit.php"></form>',
        'url' => 'https://example.com',
        'expected' => ['action' => 'https://example.com/submit.php'],
    ],
    'root-relative action is resolved' => [
        'html' => '<form action="/submit"></form>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://example.com/submit'],
    ],
    'parent-relative action is resolved' => [
        'html' => '<form action="../submit"></form>',
        'url' => 'https://example.com/a/b/page.html',
        'expected' => ['action' => 'https://example.com/a/submit'],
    ],
    'query-only action is resolved' => [
        'html' => '<form action="?page=2"></form>',
        'url' => 'https://example.com/dir/page.html?page=1',
        'expected' => ['action' => 'https://example.com/dir/page.html?page=2'],
    ],
    'relative action with query is resolved' => [
        'html' => '<form action="submit.php?x=1"></form>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://example.com/dir/submit.php?x=1'],
    ],
    'protocol-relative action is resolved' => [
        'html' => '<form action="//other.example.org/submit"></form>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://other.example.org/submit'],
    ],
    'relative action keeps the port' => [
        'html' => '<form action="/submit"></form>',
        'url' => 'http://example.com:8080/dir/page.html',
        'expected' => ['action' => 'http://example.com:8080/submit'],
    ],
    'relative action keeps user info' => [
        'html' => '<form action="/submit"></form>',
        'url' => 'https://user:pass@example.com/dir/page.html',
        'expected' => ['action' => 'https://user:pass@example.com/submit'],
    ],
    'action surrounding whitespace is ignored' => [
        'html' => '<form action="  /submit  "></form>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://example.com/submit'],
    ],
    'action is resolved against base href' => [
        'html' => '<html><head><base href="https://cdn.example.net/app/"></head><body><form action="submit"></form></body></html>',
        'url' => 'https://example.com/dir/page.html',
        'expected' => ['action' => 'https://cdn.example.net/app/submit'],
    ],
    'action with html entities is decoded' => [
        'html' => '<form action="/submit?a=1&amp;b=2"></form>',
        'url' => 'https://example.com/',
        'expected' => ['action' => 'https://example.com/submit?a=1&b=2'],
    ],
];

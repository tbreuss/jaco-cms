<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use jaco\core\data;
use jaco\core\data\sanitize;

#[TestDox('Input sanitizing')]
final class SanitizeTest extends TestCase
{
    public function testFileNameDropsTraversalAndLeadingDots(): void
    {
        $cases = [
            '../../etc/passwd' => 'etc/passwd',
            'a/./b' => 'a/b',
            '..' => '',
            'foo/../bar.html' => 'foo/bar.html',
            'win\path\x.html' => 'win/path/x.html',
            '/abs/x.html' => 'abs/x.html',
            '...hidden.html' => 'hidden.html',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, sanitize\to_filename($input), $input);
        }
    }

    public function testFileNameRejectsEverythingThatIsNotAString(): void
    {
        $this->assertSame('', sanitize\to_filename(123));
        $this->assertSame('', sanitize\to_filename(null));
        $this->assertSame('', sanitize\to_filename(['a']));
    }

    public function testTransliterateFileNameMakesNamesStorable(): void
    {
        $cases = [
            'Bildschirmfoto 2026-07-21 um 06.51.53.png' => 'Bildschirmfoto-2026-07-21-um-06-51-53.png',
            'Grüße aus Zürich.jpg' => 'Gruesse-aus-Zuerich.jpg',
            'a  b   c.gif' => 'a-b-c.gif',
            'UPPER case.PDF' => 'UPPER-case.PDF',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, sanitize\transliterate_filename($input), $input);
        }
    }

    public function testTransliterateFileNameKeepsSeparatorsForTheTraversalCheck(): void
    {
        $this->assertSame('a/b.png', sanitize\transliterate_filename('a/b.png'));
        $this->assertSame('', sanitize\transliterate_filename(123));
        $this->assertSame('', sanitize\transliterate_filename('...'));
    }

    public function testScalarSanitizers(): void
    {
        $this->assertSame(7, sanitize\to_integer('7abc'));
        $this->assertSame(0, sanitize\to_integer(['x']));
        $this->assertSame(1.5, sanitize\to_float('1.5'));
        $this->assertSame(0.0, sanitize\to_float(null));
        $this->assertSame('x', sanitize\to_string('x'));
        $this->assertSame('', sanitize\to_string(['x']));
    }

    public function testEscapeHtmlEscapesMarkup(): void
    {
        $this->assertSame('&lt;b&gt;', sanitize\escape_html('<b>'));
        $this->assertSame('', sanitize\escape_html(123));
    }

    public function testSanitizeValueAppliesTheSameRulesAndKeepsTheRest(): void
    {
        $this->assertSame('', data\sanitize_value(123, [['to_filename']]));
        $this->assertSame('a/b.html', data\sanitize_value('../../a/b.html', [['to_filename']]));
        $this->assertSame(' a/b.html ', data\sanitize_value(' a/b.html ', [['to_filename']]));
    }

    public function testUnknownSanitizerIsRejectedWithItsName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown sanitizer: filenam');

        data\sanitize(['file' => 'a.html'], [['file', 'filenam']]);
    }
}

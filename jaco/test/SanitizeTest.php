<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\data;
use jaco\core\data\sanitize;

/**
 * @testdox Input sanitizing
 */
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
            $this->assertSame($expected, sanitize\filename($input), $input);
        }
    }

    public function testFileNameRejectsEverythingThatIsNotAString(): void
    {
        $this->assertSame('', sanitize\filename(123));
        $this->assertSame('', sanitize\filename(null));
        $this->assertSame('', sanitize\filename(['a']));
    }

    public function testScalarSanitizers(): void
    {
        $this->assertSame(7, sanitize\integer('7abc'));
        $this->assertSame(0, sanitize\integer(['x']));
        $this->assertSame(1.5, sanitize\float('1.5'));
        $this->assertSame(0.0, sanitize\float(null));
        $this->assertSame('x', sanitize\string('x'));
        $this->assertSame('', sanitize\string(['x']));
    }

    public function testHtmlSpecialcharsEscapesMarkup(): void
    {
        $this->assertSame('&lt;b&gt;', sanitize\html_specialchars('<b>'));
        $this->assertSame('', sanitize\html_specialchars(123));
    }

    public function testSanitizeOneAppliesTheSameRulesAndKeepsTheRest(): void
    {
        $this->assertSame('', data\sanitize_one(123, [['filename']]));
        $this->assertSame('a/b.html', data\sanitize_one('../../a/b.html', [['filename']]));
        $this->assertSame(' a/b.html ', data\sanitize_one(' a/b.html ', [['filename']]));
    }
}

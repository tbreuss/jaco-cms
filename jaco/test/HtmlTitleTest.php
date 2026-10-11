<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\html;

#[TestDox('Page titles')]
final class HtmlTitleTest extends TestCase
{
    public function testTitleIsReadFromTheDocument(): void
    {
        $this->assertSame('Hello', html\get_title('<html><head><title>Hello</title></head><body></body></html>'));
        $this->assertSame('', html\get_title('<p>no title</p>'));
        $this->assertSame('', html\get_title(''));
    }

    public function testTitleIsReplaced(): void
    {
        $this->assertSame(
            '<html><head><title>New</title></head></html>',
            html\set_title('<html><head><title>Old</title></head></html>', 'New'),
        );
    }

    public function testTitleIsEscaped(): void
    {
        $this->assertSame(
            '<title>A &amp; B &lt;i&gt;</title>',
            html\set_title('<title>Old</title>', 'A & B <i>'),
        );
    }

    public function testDocumentWithoutTitleIsLeftUntouched(): void
    {
        // set_title only replaces an existing <title>, it does not add one
        $this->assertSame('<p>no title</p>', html\set_title('<p>no title</p>', 'New'));
    }

    public function testTitleSurvivesTheRoundTripIncludingEntities(): void
    {
        $this->assertSame(
            'Rückgabe & Mehr',
            html\get_title(html\set_title('<title>x</title>', 'Rückgabe & Mehr')),
        );
    }

    public function testNonAsciiTitleIsReadWithoutACharsetDeclaration(): void
    {
        // get_title always parses as UTF-8, a plain DOMDocument::loadHTML() would not
        $this->assertSame('Rückgabe', html\get_title('<title>Rückgabe</title>'));
        $this->assertSame('Grüße — über', html\get_title('<meta charset="utf-8"><title>Grüße — über</title>'));
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\html;

/**
 * The whitelist decides what survives a save; these rules decide what makes sense.
 *
 * An unknown name is removed silently - that is the sanitizer's job. A name that is
 * known but misplaced is an error: a property without its role, two roles on one
 * element, an item outside its repeater. Without the check the page would be saved
 * with a marker that does nothing.
 */
#[TestDox('The attribute contract is enforced on save')]
final class DataAttributeValidationTest extends TestCase
{
    public function testAValidBodyHasNoProblems(): void
    {
        $html = '<section data-jaco-repeater data-jaco-repeater--fragment="a.html"'
            . ' data-jaco-repeater--add="top" data-jaco-repeater--sortable>'
            . '<div data-jaco-repeater-item><div data-jaco-block><p>text</p></div></div>'
            . '</section>';

        $this->assertSame([], html\data_attribute_errors($html));
    }

    public function testAPropertyWithoutItsRoleIsReported(): void
    {
        $problems = html\data_attribute_errors('<img data-jaco-image--resizable src="a.png">');

        $this->assertSame(['data-jaco-image--resizable needs data-jaco-image on the same element'], $problems);
    }

    public function testTwoRolesOnOneElementAreReported(): void
    {
        $problems = html\data_attribute_errors('<div data-jaco-block data-jaco-line>text</div>');

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('one role per element', $problems[0]);
    }

    public function testAnItemOutsideARepeaterIsReported(): void
    {
        $problems = html\data_attribute_errors('<div data-jaco-repeater-item>text</div>');

        $this->assertSame(['data-jaco-repeater-item outside a repeater'], $problems);
    }

    public function testAKnownNameWithAnUnknownValueIsRemovedNotReported(): void
    {
        // the sanitizer drops the attribute; what is left is valid, so the page saves
        $body = html\sanitize_html('<section data-jaco-repeater data-jaco-repeater--fragment="a.html"'
            . ' data-jaco-repeater--add="middle">x</section>');

        $this->assertStringNotContainsString('data-jaco-repeater--add', $body);
        $this->assertSame([], html\data_attribute_errors($body));
    }

    public function testAnUnknownNameIsRemovedNotReported(): void
    {
        $body = html\sanitize_html('<div data-jaco-not-a-name>x</div>');

        $this->assertSame('<div>x</div>', $body);
        $this->assertSame([], html\data_attribute_errors($body));
    }

    public function testTheTwoEditorNamesAreNotPartOfTheContract(): void
    {
        // they never reach a page; a body that carries one breaks no pairing rule
        $this->assertSame([], html\data_attribute_errors('<img data-jaco-image src="a.png">'));
    }
}

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
        $html = '<section data-jaco-repeater="a.html"'
            . ' data-jaco-repeater--position="any" data-jaco-repeater--sortable>'
            . '<div><div data-jaco-block data-jaco-block--tools="full"><p>text</p></div></div>'
            . '</section>'
            . '<img data-jaco-image data-jaco-image--mode="fill" src="a.png" width="320">';

        $this->assertSame([], html\data_attribute_errors($html));
    }

    public function testTwoRolesOnOneElementAreReported(): void
    {
        $problems = html\data_attribute_errors('<div data-jaco-block data-jaco-line>text</div>');

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('one role per element', $problems[0]);
    }

    public function testAPropertyWithoutItsRoleIsReported(): void
    {
        $problems = html\data_attribute_errors('<div data-jaco-repeater--sortable>text</div>');

        $this->assertSame(
            ['data-jaco-repeater--sortable needs data-jaco-repeater on the same element'],
            $problems,
        );
    }

    public function testARoleInsideATextElementIsReported(): void
    {
        // the text editor owns the click, so an inner role could never be reached
        $problems = html\data_attribute_errors(
            '<div data-jaco-block><a data-jaco-link href="a.html">x</a></div>',
        );

        $this->assertSame(['role data-jaco-link inside data-jaco-block'], $problems);
    }

    public function testAnUnknownValueIsReported(): void
    {
        $problems = html\data_attribute_errors(
            '<div data-jaco-block data-jaco-block--tools="superb"><p>x</p></div>',
        );

        $this->assertSame(['unknown value for data-jaco-block--tools: superb'], $problems);
    }

    public function testFullToolsExistForBlocksOnly(): void
    {
        $problems = html\data_attribute_errors(
            '<h1 data-jaco-line data-jaco-line--tools="full">x</h1>',
        );

        $this->assertSame(['unknown value for data-jaco-line--tools: full'], $problems);
    }

    public function testAModeNeedsADimension(): void
    {
        $problems = html\data_attribute_errors(
            '<img data-jaco-image data-jaco-image--mode="fit" src="a.png">',
        );

        $this->assertSame(['data-jaco-image--mode needs width or height'], $problems);
    }

    public function testASwitchCarriesNoValue(): void
    {
        $problems = html\data_attribute_errors(
            '<section data-jaco-repeater="a.html" data-jaco-repeater--sortable="true">'
            . '<div>x</div></section>',
        );

        $this->assertSame(['data-jaco-repeater--sortable carries no value'], $problems);
    }

    public function testTextDirectlyInARepeaterIsReported(): void
    {
        $problems = html\data_attribute_errors(
            '<section data-jaco-repeater="a.html">loose text<div>x</div></section>',
        );

        $this->assertSame(['text directly in a repeater container'], $problems);
    }

    public function testAnUnknownNameIsCollectedBeforeTheSanitizerRemovesIt(): void
    {
        $html = '<div data-jaco-block data-jaco-not-a-name="x">text</div>';

        $this->assertSame(['data-jaco-not-a-name'], html\data_attribute_unknown_names($html));

        // the sanitizer removes it, which is why the save path checks before it runs
        $this->assertSame('<div data-jaco-block>text</div>', html\sanitize_html($html));
    }

    public function testAKnownNameIsNotUnknown(): void
    {
        $this->assertSame([], html\data_attribute_unknown_names(
            '<div data-jaco-block data-jaco-block--tools="basic">x</div>',
        ));
    }

    public function testAPageThatUsesAFragmentWithTwoRootsIsReported(): void
    {
        $config = test_config();
        $name = 'two-roots.html';
        file_put_contents($config->templateContentDir . '/' . $name, '<div>x</div><div>y</div>');

        try {
            $problems = html\fragment_file_problems($config, [$name]);
        } finally {
            @unlink($config->templateContentDir . '/' . $name);
        }

        $this->assertSame(['fragment without exactly one root element: ' . $name], $problems);
    }

    public function testEveryShippedFragmentHasOneRoot(): void
    {
        // the repository's own fragments, not the test sandbox: they are what the
        // demo pages insert
        $root = \dirname(__DIR__, 2) . '/jaco-data/templates/content';
        $files = \glob($root . '/*.html') ?: [];

        $this->assertNotSame([], $files, 'the repository ships fragments');

        foreach ($files as $file) {
            $content = (string) \file_get_contents($file);
            $this->assertTrue(html\fragment_has_one_root($content), \basename($file) . ' has exactly one root');
        }
    }

    public function testAFragmentThatDoesNotExistIsLeftToTheFileRules(): void
    {
        // the file value rules report it; the root check stays quiet
        $this->assertSame([], html\fragment_file_problems(test_config(), ['nosuch.html']));
    }

    public function testAFragmentWithOneRootIsAccepted(): void
    {
        $this->assertTrue(html\fragment_has_one_root("\n  <section><p>x</p></section>\n"));
    }

    public function testAFragmentWithTwoRootsIsRejected(): void
    {
        $this->assertFalse(html\fragment_has_one_root('<div>x</div><div>y</div>'));
    }

    public function testAFragmentWithTextBesideItsRootIsRejected(): void
    {
        $this->assertFalse(html\fragment_has_one_root('loose text<div>x</div>'));
    }

    public function testAnEmptyFragmentIsRejected(): void
    {
        $this->assertFalse(html\fragment_has_one_root(''));
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\html;

/**
 * The names in the code, in the document, in the frontend and in the pages have to
 * be the same set.
 *
 * A rename that updates the whitelist and forgets a selector looks green: the suites
 * never read a page, and a stripped attribute is not an error. This test compares the
 * four places and names every difference, so the work list for a half-done rename is
 * the failure output.
 *
 * The whitelist is the source (`ALLOWED_ATTRIBUTES`); the document describes it.
 */
#[TestDox('The data attribute contract is one set of names')]
final class DataAttributeContractTest extends TestCase
{
    /** @var list<string> */
    private const EDITOR_STATE = [
        'data-jaco-is-editing',
        'data-jaco-editable-image-file',
        'data-jaco-gallery-full',
        'data-jaco-editable-image-temp',
    ];

    public function testTheDocumentDescribesTheNamesInTheWhitelist(): void
    {
        $code = $this->namesInWhitelist();
        $documented = $this->namesInDocument();

        $this->assertNotSame([], $code, 'the whitelist is not empty');
        $this->assertNotSame([], $documented, 'the document lists the names');

        $this->assertSame([], $this->added($code, $documented), 'in the code, not described');
        $this->assertSame([], $this->missing($code, $documented), 'described, not in the code');
    }

    public function testTheFrontendUsesOnlyNamesFromTheWhitelist(): void
    {
        // The editor owns four names that never reach a page and are therefore not in
        // the whitelist; the document names them under "Editor state" and phase 2
        // removes them. Everything else has to be in the whitelist.
        $allowed = [...$this->namesInWhitelist(), ...self::EDITOR_STATE];

        $this->assertSame(
            [],
            $this->added($this->namesInFrontend(), $allowed),
            'a selector uses a name neither the whitelist nor the editor state knows',
        );
    }

    public function testTheStylesheetsUseOnlyNamesFromTheWhitelist(): void
    {
        // A selector on a renamed attribute silently stops styling. The name is not
        // in the whitelist either, so the pairing rules never see it - only a check
        // that reads the stylesheets does.
        $allowed = [...$this->namesInWhitelist(), ...self::EDITOR_STATE];

        $this->assertSame(
            [],
            $this->added($this->namesInStylesheets(), $allowed),
            'a stylesheet selects a name neither the whitelist nor the editor state knows',
        );
    }

    public function testTheGuidesUseOnlyNamesFromTheWhitelist(): void
    {
        // Guides tell a reader what to write today. The design document and the
        // backlog are excluded on purpose: they describe the migration and name the
        // old attributes while doing so.
        $allowed = [...$this->namesInWhitelist(), ...self::EDITOR_STATE];

        $this->assertSame(
            [],
            $this->added($this->namesInGuides(), $allowed),
            'a guide names an attribute the whitelist does not know',
        );
    }

    public function testThePagesUseOnlyNamesFromTheWhitelist(): void
    {
        $code = $this->namesInWhitelist();

        $this->assertSame(
            [],
            $this->added($this->namesInPages(), $code),
            'a page carries a name the whitelist does not know',
        );
    }

    /** @return list<string> */
    private function namesInWhitelist(): array
    {
        $names = [];
        foreach (html\ALLOWED_ATTRIBUTES['*'] as $attribute) {
            if (\str_starts_with($attribute, 'data-jaco-')) {
                $names[] = $attribute;
            }
        }

        return $this->sorted($names);
    }

    /**
     * The list the document promises, stated twice: the "Roles" and "Properties"
     * tables, and the whitelist code block under "The code is the source".
     *
     * Only those three are read. Other sections name old attributes on purpose -
     * "What exists today" describes them, the migration maps them, the length table
     * compares them, and the serialisation section quotes the old `dataAttrs` array.
     * Reading a whole section would pick those up as contract names; the patterns
     * below match the two shapes the contract is written in and nothing else.
     *
     * @return list<string>
     */
    private function namesInDocument(): array
    {
        $all = (string) \file_get_contents(\dirname(__DIR__, 2) . '/docs/attribute-mapping.md');

        $names = [];

        // the whitelist code block: 'data-jaco-x',
        $blockStart = \strpos($all, '### The code is the source');
        if ($blockStart !== false) {
            $blockEnd = \strpos($all, "\n## ", $blockStart + 1);
            $block = \substr($all, $blockStart, $blockEnd === false ? null : $blockEnd - $blockStart);
            \preg_match_all("/^\s*'(data-jaco-[a-z-]+)',$/m", $block, $quoted);
            $names = [...$names, ...$quoted[1]];
        }

        // the two tables: | `data-jaco-x` | …
        foreach (['### Roles', '### Properties'] as $heading) {
            $start = \strpos($all, $heading);
            if ($start === false) {
                continue;
            }
            $end = \strpos($all, "\n### ", $start + 1);
            $section = \substr($all, $start, $end === false ? null : $end - $start);
            \preg_match_all('/^\|\s*`(data-jaco-[a-z-]+)`\s*\|/m', $section, $rows);
            $names = [...$names, ...$rows[1]];
        }

        // the editor state is described as well, but it is not part of the contract
        return $this->sorted(\array_values(\array_diff($names, self::EDITOR_STATE)));
    }

    /**
     * Every JavaScript file below frontend/, subdirectories included: the serialiser
     * lives in lib/ and used to carry its own copy of the names, which a glob over
     * frontend/*.js did not see.
     *
     * @return list<string>
     */
    private function namesInFrontend(): array
    {
        $root = \dirname(__DIR__) . '/frontend';
        $names = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'js') {
                continue;
            }
            // the bundled libraries are not our code
            if (\str_contains($file->getPathname(), '/asset/')) {
                continue;
            }

            \preg_match_all('/data-jaco-[a-z-]+/', (string) \file_get_contents($file->getPathname()), $matches);
            $names = [...$names, ...$matches[0]];
        }

        return $this->sorted($names);
    }

    /**
     * Every attribute selector in the site and admin stylesheets.
     *
     * @return list<string>
     */
    private function namesInStylesheets(): array
    {
        $root = \dirname(__DIR__, 2);
        $names = [];

        $files = ['assets/site.css', 'jaco/frontend/asset/app.css', 'jaco/frontend/asset/editor.css'];
        foreach ($files as $relative) {
            $path = $root . '/jaco/' . $relative;
            if (!\is_file($path)) {
                $path = $root . '/' . $relative;
            }
            if (!\is_file($path)) {
                continue;
            }

            \preg_match_all('/\[data-jaco-[a-z-]+/', (string) \file_get_contents($path), $matches);
            foreach ($matches[0] as $match) {
                $names[] = \ltrim($match, '[');
            }
        }

        return $this->sorted($names);
    }

    /**
     * The documents that describe how the product is used today.
     *
     * @return list<string>
     */
    private function namesInGuides(): array
    {
        $root = \dirname(__DIR__, 2);
        $guides = [
            'README.md',
            'docs/install.md',
            'docs/glossary.md',
            'docs/constraints.md',
            'docs/features.md',
            'docs/menu-structure.md',
        ];

        $names = [];
        foreach ($guides as $relative) {
            $path = $root . '/' . $relative;
            if (!\is_file($path)) {
                continue;
            }
            \preg_match_all('/data-jaco-[a-z-]+/', (string) \file_get_contents($path), $matches);
            $names = [...$names, ...$matches[0]];
        }

        return $this->sorted($names);
    }

    /** @return list<string> */
    private function namesInPages(): array
    {
        $root = \dirname(__DIR__, 2);
        $names = [];

        foreach ($this->pageFiles($root) as $file) {
            \preg_match_all('/data-jaco-[a-z-]+/', (string) \file_get_contents($file), $matches);
            $names = [...$names, ...$matches[0]];
        }

        return $this->sorted($names);
    }

    /** @return list<string> */
    private function pageFiles(string $root): array
    {
        $files = [];
        $directories = [$root, $root . '/de', $root . '/jaco-data/templates/content', $root . '/jaco-data/templates/page', $root . '/jaco-data/templates/site'];

        foreach ($directories as $directory) {
            foreach (\glob($directory . '/*.html') ?: [] as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Names that appear in $left but not in $right, and that look like a contract name
     * (no editor state: those never reach a page and are not in the whitelist).
     *
     * @param list<string> $left
     * @param list<string> $right
     * @return list<string>
     */
    private function added(array $left, array $right): array
    {
        return $this->sorted(\array_values(\array_diff($left, $right)));
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     * @return list<string>
     */
    private function missing(array $left, array $right): array
    {
        return $this->sorted(\array_values(\array_diff($right, $left)));
    }

    /**
     * @param list<string> $names
     * @return list<string>
     */
    private function sorted(array $names): array
    {
        $names = \array_values(\array_unique($names));
        \sort($names);

        return $names;
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\html;

/**
 * The names in the code, in the frontend, in the stylesheets and in the pages have to
 * be the same set.
 *
 * A rename that updates the whitelist and forgets a selector looks green: the suites
 * never read a page, and a stripped attribute is not an error. This test compares the
 * places and names every difference, so the work list for a half-done rename is the
 * failure output.
 *
 * The whitelist is the source (`ALLOWED_ATTRIBUTES`); the document is not parsed.
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

    public function testTheGuidesWriteTemplateValuesWithTheirExtension(): void
    {
        // The name check cannot see a value. `data-jaco-template="footer-en"` used the
        // right attribute with the wrong value - the marker carries the file name, and
        // a missing extension is only visible in the value.
        $root = \dirname(__DIR__, 2);
        $offenders = [];

        foreach ($this->guideFiles() as $relative) {
            $text = (string) \file_get_contents($root . '/' . $relative);
            \preg_match_all('/data-jaco-template="([^"]*)"/', $text, $matches);
            foreach ($matches[1] as $value) {
                if (!\str_ends_with($value, '.html')) {
                    $offenders[] = $relative . ': data-jaco-template="' . $value . '"';
                }
            }
        }

        $this->assertSame([], $offenders, 'a template value without .html');
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
        $names = [];
        foreach ($this->guideFiles() as $relative) {
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
    private function guideFiles(): array
    {
        return [
            'README.md',
            'docs/install.md',
            'docs/glossary.md',
            'docs/constraints.md',
            'docs/features.md',
            'docs/menu-structure.md',
        ];
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

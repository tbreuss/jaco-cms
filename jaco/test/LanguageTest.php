<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[TestDox('Translation files')]
final class LanguageTest extends TestCase
{
    /** @return array<string, array<string, string>> */
    private function languages(): array
    {
        $languages = [];
        foreach (['en', 'de', 'fr', 'it'] as $language) {
            $path = dirname(__DIR__) . '/language/' . $language . '.json';
            $this->assertFileExists($path);
            $decoded = json_decode((string) file_get_contents($path), true);
            $this->assertIsArray($decoded, $language . '.json has to decode to an array');
            $languages[$language] = $decoded;
        }
        return $languages;
    }

    public function testAllLanguagesProvideTheSameKeys(): void
    {
        $languages = $this->languages();
        $expected = array_keys($languages['en']);
        sort($expected);

        foreach (['de', 'fr', 'it'] as $language) {
            $keys = array_keys($languages[$language]);
            sort($keys);
            $this->assertSame($expected, $keys, $language . '.json has to provide the same keys as en.json');
        }
    }

    public function testNoTranslationIsEmpty(): void
    {
        foreach ($this->languages() as $language => $messages) {
            foreach ($messages as $key => $translation) {
                $this->assertNotSame('', trim((string) $translation), $language . '.json: translation of "' . $key . '" must not be empty');
            }
        }
    }

    /**
     * Every label the frontend asks for has to exist.
     *
     * The other tests read the language files and check them among themselves;
     * they cannot see a t('...') call whose key was removed. Removing
     * "No backups are created…" from the files while the view still asked for it
     * put a literal {No backups…} on screen, and nothing caught it.
     */
    public function testEveryLabelTheFrontendAsksForExists(): void
    {
        $english = $this->languages()['en'];
        $asked = $this->labelsAskedBy('jaco/frontend');

        $this->assertNotSame([], $asked, 'the scan found labels at all');

        $missing = array_values(array_diff(array_keys($asked), array_keys($english)));

        $this->assertSame(
            [],
            $missing,
            'labels used in the frontend but missing from en.json: ' . implode(', ', $missing),
        );
    }

    /**
     * The literal first argument of every t('...') call below the given
     * directory, mapped to the file that asks for it.
     *
     * t(variable) cannot be checked statically and is skipped: the palette and the
     * theme switch translate labels that come from a command registry.
     *
     * @return array<string, string>
     */
    private function labelsAskedBy(string $directory): array
    {
        $asked = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root() . '/' . $directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || !\in_array($file->getExtension(), ['js', 'html'], true)) {
                continue;
            }

            $source = (string) \file_get_contents($file->getPathname());

            // only `t('…')` with a plain literal. A literal that is concatenated
            // (`t('Heading ' + value, …)`) or that passes its own fallback
            // (`t('Session expired', null, '…')`) needs no entry: both render
            // something readable when the key is missing.
            \preg_match_all(
                "/\bt\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/",
                $source,
                $matches,
            );

            foreach ($matches[1] as $label) {
                $asked[\stripcslashes($label)] ??= \substr($file->getPathname(), \strlen($this->root()) + 1);
            }
        }

        return $asked;
    }

    private function root(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function testServerErrorsAreTranslatable(): void
    {
        $languages = $this->languages();

        // static backend messages and validation templates (with placeholders)
        // have to exist as keys, otherwise the frontend cannot translate them
        $keys = [
            'Value cannot be blank.',
            '{value} cannot be blank.',
            '{value} must be a number.',
            '{value} must be a string.',
            '{value} is too short, minimum is {min} characters.',
            '{value} is too long, maximum is {max} characters.',
            '{value} is an invalid extension',
            '{value} is an invalid filename',
            '{value} is not writeable.',
            '{value} does not exist.',
            '{value} must not exist.',
            '{value} must be in the {dir} folder.',
            '{value} must not be in the {dir} folder.',
            '{value} is an invalid sort parameter.',
            'Authentication failed.',
            'Password needs at least 8 characters.',
            'Too many attempts. Try again in {minutes} minute(s).',
            'Could not read file.',
            'Could not write settings.',
            'The file is empty.',
            'The file exceeds the size limit.',
            'Validation Error',
            'Server Error',
        ];

        foreach ($keys as $key) {
            foreach ($languages as $language => $messages) {
                $this->assertArrayHasKey($key, $messages, $language . '.json is missing the key "' . $key . '"');
            }
        }
    }
}

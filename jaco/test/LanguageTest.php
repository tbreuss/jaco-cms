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
            'The following errors occurred',
        ];

        foreach ($keys as $key) {
            foreach ($languages as $language => $messages) {
                $this->assertArrayHasKey($key, $messages, $language . '.json is missing the key "' . $key . '"');
            }
        }
    }
}

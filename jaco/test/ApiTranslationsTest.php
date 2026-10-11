<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

/**
 * @testdox Translations API (translations)
 */
final class ApiTranslationsTest extends ApiTestCase
{
    public function testEnglishTranslationsAreAvailableWithoutAToken(): void
    {
        $response = $this->api('translations', method: 'GET', authenticated: false, query: ['language' => 'en']);

        $this->assertSame(Status::OK, $response->status);
        $this->assertStringStartsWith('{', (string) $response->body, 'a translation map has to be encoded as an object');

        $decoded = (array) json_decode((string) $response->body, true);
        $expected = $this->languageFile();

        $this->assertNotEmpty($expected);
        $this->assertSame(count($expected), count($decoded));
        $this->assertSame($expected['Login'], $decoded['Login']);
    }

    public function testUnknownLanguageFallsBackToTheDefault(): void
    {
        $default = (string) $this->api('translations', method: 'GET', authenticated: false)->body;
        $unknown = (string) $this->api(
            'translations',
            method: 'GET',
            authenticated: false,
            query: ['language' => 'xx'],
        )->body;

        $this->assertNotSame('', $default);
        $this->assertSame($default, $unknown);
    }

    public function testDefaultLanguageIsConfigurable(): void
    {
        $config = test_config_with(['defaultLanguage' => 'de']);

        $response = $this->api('translations', method: 'GET', authenticated: false, config: $config);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('Seiten', $body['Pages']);
        $this->assertSame('Anmelden', $body['Login']);
    }

    public function testMissingDefaultLanguageFallsBackToItself(): void
    {
        $config = test_config_with(['defaultLanguage' => 'xx']);

        $response = $this->api('translations', method: 'GET', authenticated: false, config: $config);

        $this->assertSame(Status::OK, $response->status);
    }

    public function testTraversalAttemptFallsBackToTheDefault(): void
    {
        $default = (string) $this->api('translations', method: 'GET', authenticated: false)->body;
        $traversal = (string) $this->api(
            'translations',
            method: 'GET',
            authenticated: false,
            query: ['language' => '../secret'],
        )->body;

        $this->assertSame($default, $traversal);
    }

    /** @return array<string, string> */
    private function languageFile(): array
    {
        $content = (string) file_get_contents(dirname(__DIR__) . '/language/en.json');

        return (array) json_decode($content, true);
    }
}

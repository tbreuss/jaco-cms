<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Status;

/**
 * Changing the interface language through the api: the choice is stored for the
 * account, the file keeps its comments, and the answer carries a fresh token so
 * the running session switches without a new login.
 */
#[TestDox('Change interface language over the api (system/change-language)')]
final class ApiChangeLanguageTest extends ApiTestCase
{
    private string $dir;

    private string $digest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/jaco-lang-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0755, true);
        mkdir($this->dir . '/jaco-data', 0755, true);

        $this->digest = (string) password_hash(TEST_PASSWORD, PASSWORD_DEFAULT, ['cost' => 4]);
        file_put_contents($this->settingsPath(), <<<PHP
            <?php declare(strict_types=1);

            // keep me, I explain the file
            return [
                'users' => [
                    'test_user' => ['password' => '{$this->digest}', 'role' => 'admin'],
                    'other_user' => ['password' => '{$this->digest}', 'role' => 'editor', 'language' => 'en'],
                ],
            ];
            PHP);
    }

    protected function tearDown(): void
    {
        @unlink($this->settingsPath());
        @rmdir($this->dir . '/jaco-data');
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function settingsPath(): string
    {
        return $this->dir . '/jaco-data/settings.php';
    }

    /** @param array<string, mixed> $overrides */
    private function config(array $overrides = []): Config
    {
        return test_config_with(array_merge([
            'appDir' => $this->dir,
            'dataDir' => $this->dir . '/jaco-data',
            'users' => [
                TEST_USERNAME => ['password' => $this->digest, 'role' => 'admin'],
                'other_user' => ['password' => $this->digest, 'role' => 'editor', 'language' => 'en'],
            ],
        ], $overrides));
    }

    /** @param array<string, string> $post */
    private function change(array $post, ?Config $config = null): \jaco\core\http\Response
    {
        return $this->api('system/change-language', $post, 'POST', true, [], $config ?? $this->config());
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return (array) include $this->settingsPath();
    }

    public function testChangesTheLanguageAndReturnsANewToken(): void
    {
        $response = $this->change(['language' => 'de']);

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertIsString($body['token']);

        // the signature is valid and the new claim already carries the choice
        $claims = auth\read_token($this->config(), $body['token']);
        $this->assertIsArray($claims);
        $this->assertSame(TEST_USERNAME, $claims['user']);
        $this->assertSame('admin', $claims['role']);
        $this->assertSame('de', $claims['language']);

        $source = (string) file_get_contents($this->settingsPath());
        $this->assertStringNotContainsString('// keep me, I explain the file', $source, 'hand-written comments go');
        $this->assertStringContainsString('Managed by Jaco', $source, 'the managed-file note replaces them');

        $settings = $this->settings();
        $this->assertSame('de', $settings['users'][TEST_USERNAME]['language']);
        $this->assertTrue(password_verify(TEST_PASSWORD, (string) $settings['users'][TEST_USERNAME]['password']));

        // the other record keeps its own choice
        $this->assertSame('en', $settings['users']['other_user']['language']);
    }

    public function testChangesTheLanguageTwice(): void
    {
        // the first save rewrites the users array in var_export()'s long syntax;
        // the second save has to find it there as well
        $this->assertSame(Status::OK, $this->change(['language' => 'de'])->status);

        $response = $this->change(['language' => 'fr']);

        $this->assertSame(Status::OK, $response->status);
        $this->assertSame('fr', $this->settings()['users'][TEST_USERNAME]['language']);
    }

    public function testRejectsAnUnknownLanguageWithoutTouchingTheFile(): void
    {
        $before = (string) file_get_contents($this->settingsPath());

        $response = $this->change(['language' => 'xx']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('language', $body[0]['source']['pointer']);
        $this->assertSame('Unknown language.', $body[0]['detail']);
        $this->assertSame($before, (string) file_get_contents($this->settingsPath()));
    }

    public function testRejectsTraversalAsALanguage(): void
    {
        // only the codes of existing translation files are accepted, so user input
        // never becomes a path
        $response = $this->change(['language' => '../en']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRejectsAMissingField(): void
    {
        // the request schema rejects the body before the domain sees it
        $response = $this->change([]);

        $this->assertSame(Status::BAD_REQUEST, $response->status);
    }

    public function testAReadOnlyAccountCannotChangeItsLanguage(): void
    {
        $this->config = $this->config(['users' => [
            TEST_USERNAME => ['password' => $this->digest, 'role' => 'admin', 'readOnly' => true],
        ]]);
        $before = (string) file_get_contents($this->settingsPath());

        $response = $this->change(['language' => 'de'], $this->config);

        $this->assertSame(Status::FORBIDDEN, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('Saving is disabled for this account', $body[0]['title']);
        $this->assertSame($before, (string) file_get_contents($this->settingsPath()), 'the file is untouched');
    }

    public function testListsTheAvailableLanguagesWithTheirNativeNames(): void
    {
        $response = $this->api('system/languages', [], 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('en', $body['defaultLanguage']);

        $languages = [];
        foreach ($body['languages'] as $language) {
            $languages[$language['code']] = $language['name'];
        }

        $this->assertSame('Deutsch', $languages['de'] ?? null);
        $this->assertSame('English', $languages['en'] ?? null);
    }

    public function testTheLanguageListNeedsALogin(): void
    {
        $response = $this->api('system/languages', [], 'GET', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

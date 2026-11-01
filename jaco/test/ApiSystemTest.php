<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('System environment API (system)')]
final class ApiSystemTest extends ApiTestCase
{
    public function testEnvironmentHasChecksConfigAndOverview(): void
    {
        $response = $this->api('system', [], 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);

        $this->assertArrayHasKey('checks', $body);
        $this->assertNotEmpty($body['checks']);
        foreach ($body['checks'] as $check) {
            $this->assertArrayHasKey('label', $check);
            $this->assertArrayHasKey('ok', $check);
            $this->assertArrayHasKey('detail', $check);
        }

        $this->assertArrayHasKey('config', $body);
        $this->assertArrayHasKey('overview', $body);

        foreach (['pages', 'uploads', 'backups', 'projectSize', 'logSize', 'logLines'] as $key) {
            $this->assertArrayHasKey($key, $body['overview']);
            $this->assertIsInt($body['overview'][$key]);
        }
    }

    public function testNoCheckPrintsAnAbsolutePath(): void
    {
        // the owner's account name and the layout of the hoster are none of the
        // admin's business: the system page shows paths relative to the web root
        $response = $this->api('system', [], 'GET');
        $checks = json_decode((string) $response->body, true)['checks'];

        $absolute = [];
        foreach ($checks as $check) {
            if (\str_contains((string) $check['detail'], $this->config->webDir)) {
                $absolute[] = $check['label'] . ': ' . $check['detail'];
            }
        }

        $this->assertSame([], $absolute, "absolute paths on the system page:\n  " . \implode("\n  ", $absolute));
    }

    public function testTheDetailsNamePathsRelativeToTheWebRoot(): void
    {
        $response = $this->api('system', [], 'GET');
        $checks = json_decode((string) $response->body, true)['checks'];

        $details = [];
        foreach ($checks as $check) {
            $details[$check['label']] = $check['detail'];
        }

        $this->assertSame('jaco/vendor/autoload.php', $details['Composer dependencies'] ?? null);
        $this->assertSame('jaco/.htaccess', $details['App protection (.htaccess)'] ?? null);
        $this->assertSame('jaco-data/.htaccess', $details['Data protection (.htaccess)'] ?? null);
        $this->assertSame('jaco/install.php', $details['Installer removed'] ?? null);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('system', [], 'GET', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    #[TestDox('The users list names role, read-only and the second factor, never the secret')]
    public function testTheConfigListsTheUsersWithoutTheirSecrets(): void
    {
        $digest = test_config_password_hash();

        // the authenticated user has to stay in the list: the token is signed for
        // it, and read_token() resolves the role from the users table
        $config = test_config_with(['users' => [
            TEST_USERNAME => ['password' => $digest, 'role' => 'admin'],
            'admin' => ['password' => $digest, 'role' => 'admin', 'totpSecret' => 'JBSWY3DPEHPK3PXP'],
            'demo' => ['password' => $digest, 'role' => 'admin', 'readOnly' => true],
            'author' => ['password' => $digest, 'role' => 'editor'],
        ]]);

        $response = $this->api('system', [], 'GET', true, [], $config);
        $this->assertSame(Status::OK, $response->status);

        $users = json_decode((string) $response->body, true)['config']['users'];

        $this->assertSame([TEST_USERNAME, 'admin', 'demo', 'author'], array_column($users, 'name'));

        $this->assertSame(
            [
                ['name' => TEST_USERNAME, 'role' => 'admin', 'readOnly' => false, 'totpEnabled' => false],
                ['name' => 'admin', 'role' => 'admin', 'readOnly' => false, 'totpEnabled' => true],
                ['name' => 'demo', 'role' => 'admin', 'readOnly' => true, 'totpEnabled' => false],
                ['name' => 'author', 'role' => 'editor', 'readOnly' => false, 'totpEnabled' => false],
            ],
            $users,
        );

        // the whole response, not only the users list: a secret that leaks through
        // a nested field is just as bad as one in the open
        $body = (string) $response->body;
        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', $body, 'the TOTP secret stays on the server');
        $this->assertStringNotContainsString('totpSecret', $body);
        $this->assertStringNotContainsString('password', $body, 'no digest either');
    }

    #[TestDox('A user without a second factor reports totpEnabled false')]
    public function testTotpEnabledIsFalseWhenTheSecretIsEmpty(): void
    {
        $config = test_config_with(['users' => [
            TEST_USERNAME => [
                'password' => test_config_password_hash(),
                'role' => 'admin',
                'totpSecret' => '',
            ],
        ]]);

        $users = json_decode((string) $this->api('system', [], 'GET', true, [], $config)->body, true)['config']['users'];

        $this->assertFalse($users[0]['totpEnabled']);
    }
}

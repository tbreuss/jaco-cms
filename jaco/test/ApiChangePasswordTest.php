<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Status;

/**
 * Changing the password through the api: the current password is the proof, the new one
 * follows the installer's rule, and the settings file keeps its comments.
 */
#[TestDox('Change password over the api (system/change-password)')]
final class ApiChangePasswordTest extends ApiTestCase
{
    private const CURRENT = 'current_pass';

    private string $dir;

    private string $digest;

    protected function setUp(): void
    {
        parent::setUp();

        auth\clear_login_history($this->config);

        $this->dir = sys_get_temp_dir() . '/jaco-pw-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0755, true);

        $this->digest = (string) password_hash(self::CURRENT, PASSWORD_DEFAULT, ['cost' => 4]);
        file_put_contents($this->settingsPath(), <<<PHP
            <?php declare(strict_types=1);

            // keep me, I explain the file
            return [
                'username' => 'demo',
                'password' => '{$this->digest}',
            ];
            PHP);
    }

    protected function tearDown(): void
    {
        @unlink($this->settingsPath());
        @rmdir($this->dir);

        auth\clear_login_history($this->config);

        parent::tearDown();
    }

    private function settingsPath(): string
    {
        return $this->dir . '/settings.php';
    }

    /** @param array<string, mixed> $overrides */
    private function config(array $overrides = []): Config
    {
        return test_config_with(array_merge([
            'appDir' => $this->dir,
            'password' => $this->digest,
        ], $overrides));
    }

    /** @param array<string, string> $post */
    private function change(array $post, ?Config $config = null): \jaco\core\http\Response
    {
        // a real request always carries an address, and without it the throttle stays out
        return $this->api(
            'system/change-password',
            $post,
            'POST',
            true,
            [],
            $config ?? $this->config(),
            ['REMOTE_ADDR' => '203.0.113.9'],
        );
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return (array) include $this->settingsPath();
    }

    public function testChangesThePasswordAndKeepsTheFile(): void
    {
        $response = $this->change([
            'withPassword' => self::CURRENT,
            'withNewPassword' => 'ein-neues-passwort',
        ]);

        $this->assertSame(Status::OK, $response->status);

        $source = (string) file_get_contents($this->settingsPath());
        $this->assertStringContainsString('// keep me, I explain the file', $source, 'comments survive');
        $this->assertSame('demo', $this->settings()['username'], 'other keys survive');

        $stored = (string) $this->settings()['password'];
        $this->assertTrue(password_verify('ein-neues-passwort', $stored), 'the new password verifies');
        $this->assertFalse(password_verify(self::CURRENT, $stored), 'the old password does not');
    }

    public function testRejectsAWrongCurrentPasswordWithoutTouchingTheFile(): void
    {
        $before = (string) file_get_contents($this->settingsPath());

        $response = $this->change([
            'withPassword' => 'wrong',
            'withNewPassword' => 'ein-neues-passwort',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('withPassword', $body[0]['source']['pointer']);
        $this->assertSame('Authentication failed.', $body[0]['detail']);
        $this->assertSame($before, (string) file_get_contents($this->settingsPath()));
    }

    public function testRejectsANewPasswordBelowTheInstallerMinimum(): void
    {
        $response = $this->change([
            'withPassword' => self::CURRENT,
            'withNewPassword' => 'short',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('withNewPassword', $body[0]['source']['pointer']);
        $this->assertSame('Password needs at least 8 characters.', $body[0]['detail']);
    }

    public function testRejectsMissingFields(): void
    {
        // the request schema rejects the body before the domain sees it
        $response = $this->change([]);

        $this->assertSame(Status::BAD_REQUEST, $response->status);
    }

    public function testAWrongCurrentPasswordCountsAgainstTheThrottle(): void
    {
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            $this->change(['withPassword' => 'wrong', 'withNewPassword' => 'ein-neues-passwort']);
        }

        // the fourth attempt is blocked before the password is even looked at
        $response = $this->change([
            'withPassword' => self::CURRENT,
            'withNewPassword' => 'ein-neues-passwort',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $body[0]['detail']);
    }

    public function testWorksWhileMaintenanceModeIsOn(): void
    {
        // the way back in must not be blocked by the mode it is meant to recover from
        $response = $this->change([
            'withPassword' => self::CURRENT,
            'withNewPassword' => 'ein-neues-passwort',
        ], $this->config(['maintenanceMode' => true]));

        $this->assertSame(Status::OK, $response->status);
    }
}

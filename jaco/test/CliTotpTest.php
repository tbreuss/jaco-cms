<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\core\config\Config;

use function jaco\cli\auth\totp\disable_command;
use function jaco\cli\auth\totp\setup_command;

/**
 * auth:totp-setup writes the secret into the named user record and nowhere else.
 * A command that reports success must not leave the login without a second factor.
 */
#[TestDox('CLI two-factor setup is bound to one user')]
final class CliTotpTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/jaco-cli-totp-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0755, true);
        mkdir($this->dir . '/jaco-data', 0755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/jaco-data/settings.php');
        @rmdir($this->dir . '/jaco-data');
        @rmdir($this->dir);
    }

    private function writeTwoUsers(bool $annaHasSecret = false): void
    {
        $anna = "'password' => 'hash-a', 'role' => 'admin'"
            . ($annaHasSecret ? ", 'totpSecret' => 'ANNASECRET'" : '');

        file_put_contents($this->dir . '/jaco-data/settings.php', <<<PHP
            <?php declare(strict_types=1);

            // keep me, I explain the file
            return [
                'users' => [
                    'anna' => [{$anna}],
                    'bob' => ['password' => 'hash-b', 'role' => 'editor', 'totpSecret' => 'BOBSECRET'],
                ],
                'secretKey' => 'do-not-touch',
            ];
            PHP);
    }

    private function writeOneUser(): void
    {
        file_put_contents($this->dir . '/jaco-data/settings.php', <<<'PHP'
            <?php declare(strict_types=1);

            // keep me, I explain the file
            return [
                'users' => [
                    'anna' => ['password' => 'hash-a', 'role' => 'admin'],
                ],
                'secretKey' => 'do-not-touch',
            ];
            PHP);
    }

    /** @param array<string, array<string, mixed>> $users */
    private function config(array $users): Config
    {
        return test_config_with([
            'appDir' => $this->dir,
            'dataDir' => $this->dir . '/jaco-data',
            'users' => $users,
        ]);
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return (array) include $this->dir . '/jaco-data/settings.php';
    }

    public function testSetupWritesTheSecretIntoTheNamedUser(): void
    {
        $this->writeTwoUsers();

        [$output, $code] = setup_command($this->config($this->settings()['users']), 'anna');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Two-factor authentication is now required for "anna".', $output);

        $source = (string) file_get_contents($this->dir . '/jaco-data/settings.php');
        $this->assertStringContainsString('// keep me, I explain the file', $source, 'comments survive');
        $this->assertStringContainsString("'secretKey' => 'do-not-touch'", $source);

        $settings = $this->settings();
        $this->assertIsString($settings['users']['anna']['totpSecret'] ?? null);
        $this->assertNotSame('', $settings['users']['anna']['totpSecret']);
        $this->assertSame('BOBSECRET', $settings['users']['bob']['totpSecret'], 'the other user is untouched');
        $this->assertStringContainsString($settings['users']['anna']['totpSecret'], $output);
    }

    public function testDisableRemovesTheSecretOfOneUserOnly(): void
    {
        $this->writeTwoUsers(true);

        [$output, $code] = disable_command($this->config($this->settings()['users']), 'anna');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('disabled for "anna"', $output);

        $settings = $this->settings();
        $this->assertArrayNotHasKey('totpSecret', $settings['users']['anna']);
        $this->assertSame('BOBSECRET', $settings['users']['bob']['totpSecret']);
    }

    public function testWithoutAUserTheCommandRefusesToGuess(): void
    {
        $this->writeTwoUsers();
        $before = (string) file_get_contents($this->dir . '/jaco-data/settings.php');

        [$output, $code] = setup_command($this->config($this->settings()['users']));

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--user', $output);
        $this->assertSame($before, (string) file_get_contents($this->dir . '/jaco-data/settings.php'), 'nothing is written');
    }

    public function testAnUnknownUserIsRejected(): void
    {
        $this->writeTwoUsers();

        [$output, $code] = setup_command($this->config($this->settings()['users']), 'carla');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Unknown user "carla".', $output);
    }

    public function testASingleUserAlsoNeedsAName(): void
    {
        $this->writeOneUser();
        $before = (string) file_get_contents($this->dir . '/jaco-data/settings.php');

        [$output, $code] = setup_command($this->config($this->settings()['users']));

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--user', $output);
        $this->assertSame($before, (string) file_get_contents($this->dir . '/jaco-data/settings.php'), 'nothing is written');
    }

    public function testASingleUserIsEnrolledWhenNamed(): void
    {
        $this->writeOneUser();

        [$output, $code] = setup_command($this->config($this->settings()['users']), 'anna');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('for "anna"', $output);

        $user = auth\find_user($this->config($this->settings()['users']), 'anna');
        $this->assertIsArray($user);
        $this->assertNotSame('', $user['totpSecret']);
    }
}

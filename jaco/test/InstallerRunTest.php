<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\auth;
use jaco\core\config;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * The installer as a whole: a request against a fresh web root has to leave a
 * working installation behind.
 *
 * The other installer tests check single helpers. They cannot see an order
 * problem - a directory created after the settings are written, a deny file that
 * depends on data that does not exist yet - and those are the failures that make
 * an installation impossible to finish.
 */
#[TestDox('Installer end to end')]
final class InstallerRunTest extends TestCase
{
    private const USERNAME = 'installer_admin';
    private const PASSWORD = 'a-long-enough-password';

    private string $webRoot = '';

    protected function tearDown(): void
    {
        if ($this->webRoot !== '') {
            $this->removeDir($this->webRoot);
            $this->webRoot = '';
        }
    }

    public function testAFreshRequestInstallsTheSite(): void
    {
        $result = $this->request(['username' => self::USERNAME, 'password' => self::PASSWORD]);

        $this->assertSame(0, $result['status'], $result['body']);
        $this->assertStringContainsString('Login <strong>' . self::USERNAME . '</strong> works.', $result['body']);
        $this->assertStringContainsString('The installer deleted itself as expected.', $result['body']);
        $this->assertFileDoesNotExist($this->webRoot . '/jaco/install.php', 'a second visit cannot install again');
    }

    public function testTheInstallationIsComplete(): void
    {
        $this->request(['username' => self::USERNAME, 'password' => self::PASSWORD]);

        $this->assertFileExists($this->webRoot . '/jaco-data/settings.php');
        $this->assertFileExists($this->webRoot . '/jaco-data/.htaccess', 'the deny file of the operator data');
        $this->assertDirectoryExists($this->webRoot . '/jaco-data/backups');
        $this->assertDirectoryExists($this->webRoot . '/jaco-data/templates/site');
        $this->assertDirectoryExists($this->webRoot . '/jaco-data/runtime/log');
        $this->assertFileExists($this->webRoot . '/media/.htaccess', 'the deny file of the uploads');
    }

    public function testTheWrittenSettingsAllowTheLogin(): void
    {
        $this->request(['username' => self::USERNAME, 'password' => self::PASSWORD]);

        $written = require $this->webRoot . '/jaco-data/settings.php';
        $config = config\create_default_config($this->webRoot . '/jaco', \is_array($written) ? $written : []);

        $this->assertIsString(auth\authenticate($config, self::USERNAME, self::PASSWORD), 'the account can log in');
        $this->assertNull(auth\authenticate($config, self::USERNAME, 'wrong-password'));
        $this->assertStringStartsWith('$2y$', (string) $written['users'][self::USERNAME]['password'], 'the password is hashed');
    }

    public function testAShortPasswordIsRefused(): void
    {
        $result = $this->request(['username' => self::USERNAME, 'password' => 'short']);

        $this->assertStringContainsString('Password needs at least 8 characters.', $result['body']);
        $this->assertFileDoesNotExist($this->webRoot . '/jaco-data/settings.php', 'nothing is written on an error');
        $this->assertFileExists($this->webRoot . '/jaco/install.php', 'the installer stays for another attempt');
    }

    public function testAnInstalledSiteRefusesASecondInstallation(): void
    {
        $this->request(['username' => self::USERNAME, 'password' => self::PASSWORD]);

        // the installer deleted itself; a hand-copied one has to refuse as well
        \copy(\dirname(__DIR__) . '/install.php', $this->webRoot . '/jaco/install.php');
        $result = $this->request(['username' => 'intruder', 'password' => self::PASSWORD], prepareAgain: false);

        $this->assertStringContainsString('already installed', $result['body']);
        $written = require $this->webRoot . '/jaco-data/settings.php';
        $this->assertArrayNotHasKey('intruder', (array) $written['users'], 'the first account is kept');
    }

    /**
     * Calls the installer the way a browser does: a child PHP process that sets
     * the request variables the installer reads and includes it.
     *
     * @param array<string, string> $post
     * @return array{status: int, body: string}
     */
    private function request(array $post, bool $prepareAgain = true): array
    {
        if ($prepareAgain) {
            $this->prepare();
        }

        $runner = $this->webRoot . '/run-installer.php';
        $installer = \var_export($this->webRoot . '/jaco/install.php', true);
        $form = \var_export($post, true);

        \file_put_contents($runner, <<<PHP
            <?php
            \$_SERVER['REQUEST_METHOD'] = 'POST';
            \$_SERVER['SCRIPT_FILENAME'] = $installer;
            \$_SERVER['SERVER_NAME'] = 'localhost';
            \$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            \$_POST = $form;
            require $installer;
            PHP);

        \exec(\escapeshellarg(PHP_BINARY) . ' ' . \escapeshellarg($runner) . ' 2>&1', $output, $status);
        @\unlink($runner);

        return ['status' => $status, 'body' => \implode("\n", $output)];
    }

    private function prepare(): void
    {
        $this->webRoot = \sys_get_temp_dir() . '/jaco-installer-' . \bin2hex(\random_bytes(4));
        \mkdir($this->webRoot . '/jaco', 0755, true);
        \copy(\dirname(__DIR__) . '/install.php', $this->webRoot . '/jaco/install.php');
        // the installer boots through the autoloader of the real application
        \symlink(\dirname(__DIR__) . '/vendor', $this->webRoot . '/jaco/vendor');
    }

    private function removeDir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isLink() || $item->isFile()) {
                @\unlink($item->getPathname());
            } elseif ($item->isDir()) {
                @\rmdir($item->getPathname());
            }
        }

        @\rmdir($dir);
    }
}

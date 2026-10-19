<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\core\config;
use jaco\core\system;

#[TestDox('Installer helpers')]
final class InstallTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/jaco-install-test';
        $this->removeDir($this->dir);
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->dir);
    }

    public function testChecksReportEveryRequirementInOrder(): void
    {
        $config = config\create_default_config($this->dir, []);
        $labels = array_column(system\install_checks($config), 'label');

        $this->assertSame(
            [
                'PHP version',
                'PHP extension dom',
                'PHP extension libxml',
                'PHP extension openssl',
                'Composer dependencies',
                'Configuration writable',
                'Backup directory',
                'Upload directory',
                'Log directory',
            ],
            $labels,
        );
    }

    public function testMissingVendorIsReported(): void
    {
        // the temp directory holds no vendor directory by design
        $config = config\create_default_config($this->dir, []);
        $checks = system\install_checks($config);

        $this->assertFalse($checks[4]['ok']);
        $this->assertStringEndsWith('/vendor/autoload.php', $checks[4]['detail']);
    }

    public function testMissingDirectoriesAreCreatable(): void
    {
        $config = config\create_default_config($this->dir, []);
        $checks = system\install_checks($config);

        // nothing exists below the temp directory, but the parent is
        // writable, so the installer may create them
        $this->assertTrue($checks[6]['ok']);
        $this->assertTrue($checks[7]['ok']);
        $this->assertTrue($checks[8]['ok']);
    }

    public function testWrittenSettingsLogIn(): void
    {
        // quotes and backslashes prove the escaping roundtrips exactly; the
        // password is hashed, like every password in a real settings.php
        $settings = [
            'users' => ["ad'min" => ['password' => \password_hash('sec\\ret123', PASSWORD_DEFAULT), 'role' => 'admin']],
            'secretKey' => 'key',
        ];

        $this->assertTrue(config\write_settings($this->dir, $settings));

        $written = require $this->dir . '/settings.php';
        $config = config\create_default_config($this->dir, is_array($written) ? $written : []);
        $this->assertSame($settings, $written);
        $this->assertIsString(auth\authenticate($config, "ad'min", 'sec\\ret123'));
        $this->assertNull(auth\authenticate($config, "ad'min", 'wrong'));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);

        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}

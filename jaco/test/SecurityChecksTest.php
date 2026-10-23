<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\system;

#[TestDox('Hardening checks')]
final class SecurityChecksTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/jaco-security-test';
        $this->removeDir($this->dir);
        mkdir($this->dir . '/_backup', 0755, true);
        mkdir($this->dir . '/runtime/log', 0755, true);
        mkdir($this->dir . '/vendor', 0755, true);
        mkdir($this->dir . '/upload', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->dir);
    }

    public function testAllProtectionsPresent(): void
    {
        foreach (['/.htaccess', '/_backup/.htaccess', '/runtime/.htaccess', '/vendor/.htaccess'] as $file) {
            file_put_contents($this->dir . $file, 'Require all denied');
        }

        $checks = system\security_checks($this->config());

        $this->assertSame(
            [
                'App protection (.htaccess)',
                'Backup protection (.htaccess)',
                'Log protection (.htaccess)',
                'Vendor protection (.htaccess)',
                'Installer removed',
                'Settings not writable',
                'Secret key set',
                'Upload directory writable',
                'Directory permissions not too open',
            ],
            array_column($checks, 'label'),
        );
        $this->assertSame(array_fill(0, 9, true), array_column($checks, 'ok'));
    }

    public function testMissingSecretKeyIsReported(): void
    {
        $checks = system\security_checks($this->config(false));

        $this->assertFalse($checks[6]['ok']);
        $this->assertSame('not configured (random fallback)', $checks[6]['detail']);
    }

    public function testMissingProtectionIsReported(): void
    {
        file_put_contents($this->dir . '/.htaccess', 'Require all denied');

        $checks = system\security_checks($this->config());

        $this->assertTrue($checks[0]['ok']);
        $this->assertFalse($checks[1]['ok']);
        $this->assertFalse($checks[2]['ok']);
        $this->assertFalse($checks[3]['ok']);
        $this->assertStringEndsWith('/_backup/.htaccess', $checks[1]['detail']);
    }

    private function config(bool $secretKeyIsConfigured = true): \jaco\core\config\Config
    {
        return test_config_with([
            'appDir' => $this->dir,
            'backupDir' => $this->dir . '/_backup',
            'loginHistoryFile' => $this->dir . '/runtime/log/loginhistory.json',
            'secretKey' => str_repeat('a', 32),
            'secretKeyIsConfigured' => $secretKeyIsConfigured,
        ]);
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

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\core\config;

#[TestDox('Credential defaults fail closed')]
final class ConfigDefaultsTest extends TestCase
{
    public function testEmptySettingsCannotLogIn(): void
    {
        $config = config\create_default_config(sys_get_temp_dir(), [
            'username' => '',
            'password' => '',
            'secretKey' => '',
        ]);

        $this->assertNull(auth\authenticate($config, '', ''));
        $this->assertNull(auth\authenticate($config, 'admin', ''));
    }

    public function testEmptyValuesFallBackToRandom(): void
    {
        $config = config\create_default_config(sys_get_temp_dir(), [
            'username' => '',
            'password' => '',
            'secretKey' => '',
        ]);

        $this->assertSame([], $config->users);
        $this->assertGreaterThanOrEqual(32, \strlen($config->secretKey));
        $this->assertFalse($config->secretKeyIsConfigured);
    }

    public function testShortSecretKeyIsReplaced(): void
    {
        $config = config\create_default_config(sys_get_temp_dir(), ['secretKey' => 'z2MSdMsCEs8eQpZV']);

        $this->assertNotSame('z2MSdMsCEs8eQpZV', $config->secretKey);
        $this->assertGreaterThanOrEqual(32, \strlen($config->secretKey));
    }

    public function testValidValuesPassThrough(): void
    {
        $secret = str_repeat('a', 32);
        // the password is stored hashed, like a real settings.php
        $config = config\create_default_config(sys_get_temp_dir(), [
            'users' => ['admin' => ['password' => \password_hash('secure-password', PASSWORD_DEFAULT), 'role' => 'admin']],
            'secretKey' => $secret,
        ]);

        $this->assertSame(['password', 'role'], array_keys($config->users['admin']));
        $this->assertSame($secret, $config->secretKey);
        $this->assertTrue($config->secretKeyIsConfigured);
        $this->assertIsString(auth\authenticate($config, 'admin', 'secure-password'));
    }

    public function testUploadDirectoryIsConfigurable(): void
    {
        $config = config\create_default_config('/var/www/site/jaco', ['mediaDir' => 'bilder/2026']);

        $this->assertSame('/var/www/site/bilder/2026', $config->mediaDir);
        $this->assertSame('bilder/2026', $config->mediaUrl);
    }

    public function testUnsafeUploadDirectoryFallsBackToTheDefault(): void
    {
        foreach (['../etc', '/abs/path', 'up/../load', 'has space', 'a;b', ''] as $unsafe) {
            $config = config\create_default_config('/var/www/site/jaco', ['mediaDir' => $unsafe]);

            $this->assertSame('/var/www/site/media', $config->mediaDir, $unsafe);
            $this->assertSame('media', $config->mediaUrl, $unsafe);
        }
    }

    public function testOperatorDataLivesOutsideTheAppDirectory(): void
    {
        $config = config\create_default_config('/var/www/site/jaco');

        $this->assertSame('/var/www/site/jaco-data', $config->dataDir);
        $this->assertSame('/var/www/site/jaco-data/backups', $config->backupDir);
        $this->assertSame('/var/www/site/jaco-data/templates/page', $config->templatePageDir);
        $this->assertSame('/var/www/site/jaco-data/templates/content', $config->templateContentDir);
        $this->assertSame('/var/www/site/jaco-data/templates/site', $config->templateSiteDir);
    }

    public function testOperatorDataDirectoryIsConfigurable(): void
    {
        $config = config\create_default_config('/var/www/site/jaco', ['dataDir' => 'site-data']);

        $this->assertSame('/var/www/site/site-data', $config->dataDir);
        $this->assertSame('/var/www/site/site-data/backups', $config->backupDir);
    }

    public function testUnsafeDataDirectoryFallsBackToTheDefault(): void
    {
        foreach (['../etc', '/abs/path', 'a/../b', 'has space', ''] as $unsafe) {
            $config = config\create_default_config('/var/www/site/jaco', ['dataDir' => $unsafe]);

            $this->assertSame('/var/www/site/jaco-data', $config->dataDir, $unsafe);
        }
    }

    public function testEmptyStoredCredentialsNeverAuthenticate(): void
    {
        $config = test_config_with(['users' => []]);

        $this->assertNull(auth\authenticate($config, '', ''));
    }
}

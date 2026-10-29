<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\config\Config;
use jaco\core\filesystem;

use function jaco\domain\update\perform_update;
use function jaco\domain\update\find_jaco_dir;

#[TestDox('Self-update')]
final class UpdateTest extends TestCase
{
    private string $root;
    private string $appDir;
    private string $webDir;
    private string $customDataDir;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/jaco-update-test-' . uniqid('', true);
        $this->appDir = $this->root . '/jaco';
        $this->webDir = $this->root;
        $this->customDataDir = $this->webDir . '/custom-data';

        // build a minimal app dir
        mkdir($this->appDir, 0755, true);
        file_put_contents($this->appDir . '/api.php', "<?php\n");
        file_put_contents($this->appDir . '/autoload.php', "<?php\n");

        // custom data dir
        mkdir($this->customDataDir . '/backups', 0755, true);
        mkdir($this->customDataDir . '/app-backups', 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            filesystem\remove_dir_rec(dirname($this->root), basename($this->root));
        }
    }

    private function config(): Config
    {
        return new Config(
            appVersion: '0.1.0',
            appDir: $this->appDir,
            appDirName: 'jaco',
            webDir: $this->webDir,
            dataDir: $this->customDataDir,
            backupDir: $this->customDataDir . '/backups',
            appBackupDir: $this->customDataDir . '/app-backups',
            templatePageDir: $this->customDataDir . '/templates/page',
            templateContentDir: $this->customDataDir . '/templates/content',
            templateSiteDir: $this->customDataDir . '/templates/site',
            mediaDir: $this->webDir . '/media',
            uploadExtensions: ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf'],
            mediaUrl: 'media',
            pageExtensions: ['htm', 'html'],
            defaultLanguage: 'en',
            directoryPermissions: 0755,
            filePermissions: 0644,
            secretKey: 'test_1234',
            users: [],
            phpErrorReporting: 0,
            phpDisplayErrors: 0,
            phpDisplayStartupErrors: 0,
            phpLogErrors: 0,
            phpErrorLog: $this->customDataDir . '/runtime/log/error.log',
            loginHistoryFile: $this->customDataDir . '/runtime/log/loginhistory.json',
            maintenanceMode: false,
            backupRetentionDays: 90,
        );
    }

    private function makeZip(string $name, array $entries): string
    {
        $path = sys_get_temp_dir() . '/' . $name;

        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($entries as $internal => $content) {
                $zip->addFromString($internal, $content);
            }

            $zip->close();
        } else {
            $tmp = sys_get_temp_dir() . '/zipsrc-' . uniqid();
            mkdir($tmp, 0755, true);

            foreach ($entries as $internal => $content) {
                $dir = dirname($tmp . '/' . $internal);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($tmp . '/' . $internal, $content);
            }

            shell_exec('cd ' . escapeshellarg($tmp) . ' && zip -qr ' . escapeshellarg($path) . ' .');
            filesystem\remove_dir_rec(dirname($tmp), basename($tmp));
        }

        return $path;
    }

    public function testInvalidZipIsRejected(): void
    {
        $zipPath = sys_get_temp_dir() . '/invalid.zip';
        file_put_contents($zipPath, 'not a zip');

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath);

        $this->assertFalse($payload->isSuccess);
        @unlink($zipPath);
    }

    public function testArchiveWithoutJacoDirIsRejected(): void
    {
        $zipPath = $this->makeZip('nojaco.zip', [
            'readme.txt' => 'hello',
        ]);

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath);

        $this->assertFalse($payload->isSuccess);
        @unlink($zipPath);
    }

    public function testValidUpdateWithCustomDataDir(): void
    {
        $zipPath = $this->makeZip('valid.zip', [
            'jaco/api.php' => "<?php\n",
            'jaco/autoload.php' => "<?php\n",
        ]);

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath);

        $this->assertTrue($payload->isSuccess, 'Update should succeed');
        $this->assertFileExists($this->appDir . '/api.php');
        $this->assertDirectoryExists($this->customDataDir . '/app-backups');

        $backups = glob($this->customDataDir . '/app-backups/jaco-*');
        $this->assertNotEmpty($backups, 'Backup should be created in custom app-backups dir');

        @unlink($zipPath);
    }

    public function testFindJacoDirNested(): void
    {
        $root = sys_get_temp_dir() . '/findjaco-' . uniqid();
        mkdir($root . '/release-name/jaco', 0755, true);
        file_put_contents($root . '/release-name/jaco/api.php', '<?php');

        $this->assertSame($root . '/release-name/jaco', find_jaco_dir($root));

        filesystem\remove_dir_rec(dirname($root), basename($root));
    }

    public function testFindJacoDirFlat(): void
    {
        $root = sys_get_temp_dir() . '/findjaco-' . uniqid();
        mkdir($root, 0755, true);
        file_put_contents($root . '/api.php', '<?php');
        file_put_contents($root . '/autoload.php', '<?php');

        $this->assertSame($root, find_jaco_dir($root));

        filesystem\remove_dir_rec(dirname($root), basename($root));
    }

    public function testDowngradeIsRejected(): void
    {
        $zipPath = $this->makeZip('downgrade.zip', [
            'jaco/api.php' => "<?php\n",
            'jaco/autoload.php' => "<?php\n",
        ]);

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath, 'v0.0.1');

        $this->assertFalse($payload->isSuccess);
        $this->assertStringContainsString('Downgrade not allowed', (string) ($payload->data[0][1] ?? ''));

        @unlink($zipPath);
    }

    public function testUntrustedUrlIsRejected(): void
    {
        $config = $this->config();
        $payload = perform_update($config, 'http://example.com/x.zip');

        $this->assertFalse($payload->isSuccess);
        $this->assertStringContainsString('HTTPS', (string) ($payload->data[0][1] ?? ''));

        $payload2 = perform_update($config, 'https://evil.com/x.txt');

        $this->assertFalse($payload2->isSuccess);
        $this->assertStringContainsString('.zip', (string) ($payload2->data[0][1] ?? ''));
    }

    public function testDirectUrlDoesNotCheckUpToDate(): void
    {
        $zipPath = $this->makeZip('direct.zip', [
            'jaco/api.php' => "<?php\n",
            'jaco/autoload.php' => "<?php\n",
        ]);

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath);

        // direct URL installs without checking up-to-date status
        $this->assertTrue($payload->isSuccess);
        $this->assertArrayNotHasKey('upToDate', $payload->data ?? []);

        @unlink($zipPath);
    }
}
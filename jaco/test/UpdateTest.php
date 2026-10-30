<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\config\Config;
use jaco\core\filesystem;

use function jaco\domain\update\perform_update;
use function jaco\domain\update\find_jaco_dir;
use function jaco\domain\update\missing_extractor_error;
use function jaco\domain\update\checksum_url;
use function jaco\domain\update\is_release_asset_url;
use function jaco\domain\update\is_release_download_url;
use function jaco\domain\update\response_final_url;
use function jaco\domain\update\verify_release_checksum;

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

        // zip is a suggested extension: the tests that build an archive for the
        // extractor need it, the ones that do not must still run without it
        if (extension_loaded('zip')) {
            $zip = new \ZipArchive();
            $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($entries as $internal => $content) {
                $zip->addFromString($internal, $content);
            }

            $zip->close();

            return $path;
        }

        $tmp = sys_get_temp_dir() . '/zipsrc-' . uniqid('', true);
        mkdir($tmp, 0755, true);

        foreach ($entries as $internal => $content) {
            $dir = dirname($tmp . '/' . $internal);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($tmp . '/' . $internal, $content);
        }

        // no shell_exec: zip is a build tool on the CI image, not a runtime dependency
        $command = 'cd ' . escapeshellarg($tmp) . ' && zip -qr ' . escapeshellarg($path) . ' .';
        exec($command, $output, $status);
        filesystem\remove_dir_rec(dirname($tmp), basename($tmp));

        if ($status !== 0) {
            self::markTestSkipped('Neither ext-zip nor the zip binary is available.');
        }

        return $path;
    }

    #[TestDox('A host without zip can still check for updates, only installing is blocked')]
    public function testMissingZipExtension(): void
    {
        // zip is suggested, not required: an installation without it keeps running
        $this->assertNull(missing_extractor_error(fn(string $extension): bool => $extension === 'zip'));

        $error = missing_extractor_error(fn(string $extension): bool => false);

        $this->assertNotNull($error);
        $this->assertStringContainsString('php-zip', $error);
    }

    public function testInvalidZipIsRejected(): void
    {
        $zipPath = sys_get_temp_dir() . '/invalid.zip';
        file_put_contents($zipPath, 'not a zip');

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath, null, allowLocalFile: true);

        $this->assertFalse($payload->isSuccess);
        @unlink($zipPath);
    }

    public function testArchiveWithoutJacoDirIsRejected(): void
    {
        $zipPath = $this->makeZip('nojaco.zip', [
            'readme.txt' => 'hello',
        ]);

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath, null, allowLocalFile: true);

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
        $payload = perform_update($config, 'file://' . $zipPath, null, allowLocalFile: true);

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
        $payload = perform_update($config, 'file://' . $zipPath, 'v0.0.1', allowLocalFile: true);

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
        $this->assertStringContainsString('release', (string) ($payload2->data[0][1] ?? ''));
    }

    #[TestDox('A web request cannot install a local file')]
    public function testFileUrlIsRejectedByDefault(): void
    {
        $zipPath = $this->makeZip('web-local.zip', [
            'jaco/api.php' => "<?php\n",
            'jaco/autoload.php' => "<?php\n",
        ]);

        // the api action never sets allowLocalFile, so this is the web default
        $payload = perform_update($this->config(), 'file://' . $zipPath);

        $this->assertFalse($payload->isSuccess);
        $this->assertStringContainsString('HTTPS', (string) ($payload->data[0][1] ?? ''));
        $this->assertCount(0, (array) glob($this->customDataDir . '/app-backups/jaco-*'));

        @unlink($zipPath);
    }

    public function testDirectUrlDoesNotCheckUpToDate(): void
    {
        $zipPath = $this->makeZip('direct.zip', [
            'jaco/api.php' => "<?php\n",
            'jaco/autoload.php' => "<?php\n",
        ]);

        $config = $this->config();
        $payload = perform_update($config, 'file://' . $zipPath, null, allowLocalFile: true);

        // direct URL installs without checking up-to-date status
        $this->assertTrue($payload->isSuccess);
        $this->assertArrayNotHasKey('upToDate', $payload->data ?? []);

        @unlink($zipPath);
    }

    #[TestDox('An HTTPS archive from anywhere but the release is refused')]
    public function testForeignHttpsUrlIsRejectedBeforeDownload(): void
    {
        $config = $this->config();

        foreach ([
            'https://evil.com/jaco-dist-0.2.0.zip',
            'https://github.com/attacker/jaco-dist-0.2.0.zip',
            'https://raw.githubusercontent.com/tbreuss/jaco-cms/main/jaco-dist-0.2.0.zip',
            'https://api.github.com/repos/tbreuss/jaco-cms/releases/download/v0.2.0/jaco-dist-0.2.0.zip',
            'https://github.com/tbreuss/jaco-cms/archive/refs/heads/main.zip',
            'https://github.com/tbreuss/jaco-cms/releases/download/v0.2.0/payload.zip',
        ] as $url) {
            $payload = perform_update($config, $url);

            $this->assertFalse($payload->isSuccess, $url . ' must be refused');
            $this->assertStringContainsString('release', (string) ($payload->data[0][1] ?? ''), $url);
        }

        // nothing was downloaded, so no backup was taken
        $this->assertCount(0, (array) glob($this->customDataDir . '/app-backups/jaco-*'));
    }

    #[TestDox('The release download URLs are recognised')]
    public function testReleaseDownloadUrl(): void
    {
        $this->assertTrue(is_release_download_url(
            'https://github.com/tbreuss/jaco-cms/releases/download/v0.2.0/jaco-dist-v0.2.0.zip',
        ));
        // the signed CDN URL is a redirect target, never a configured download URL
        $this->assertFalse(is_release_download_url(
            'https://objects.githubusercontent.com/github-production-release-asset-2e65be/123/456/jaco-dist-v0.2.0.zip?X-Amz-Signature=abc',
        ));

        $this->assertFalse(is_release_download_url('https://evil.com/jaco-dist-v0.2.0.zip'));
        $this->assertFalse(is_release_download_url('https://github.com/someone/else/releases/download/v0.2.0/jaco-dist-x.zip'));
        $this->assertFalse(is_release_download_url('http://github.com/tbreuss/jaco-cms/releases/download/v0.2.0/jaco-dist.zip'));
        $this->assertFalse(is_release_download_url('https://github.com/tbreuss/jaco-cms/releases/tags/v0.2.0'));
        $this->assertFalse(is_release_download_url('https://github.com/tbreuss/jaco-cms/releases/download/v0.2.0/payload.zip'));
        $this->assertFalse(is_release_download_url('file:///tmp/jaco-dist-v0.2.0.zip'));
        // a redirect-shaped URL is not a release download URL either
        $this->assertFalse(is_release_download_url(
            'https://objects.githubusercontent.com/github-production-release-asset-2e65be/123/456?X-Amz-Signature=abc',
        ));
    }

    #[TestDox('The redirect target of a verified asset is accepted, anything else is not')]
    public function testReleaseAssetUrl(): void
    {
        $releaseUrl = 'https://github.com/tbreuss/jaco-cms/releases/download/v0.2.0/jaco-dist-v0.2.0.zip';

        // GitHub redirects to a signed URL that carries the asset name
        $this->assertTrue(is_release_asset_url(
            'https://objects.githubusercontent.com/github-production-release-asset-2e65be/123/'
            . 'jaco-dist-v0.2.0.zip?X-Amz-Signature=abc',
            $releaseUrl,
        ));

        // a different asset, a different host or another release is not
        $this->assertFalse(is_release_asset_url('https://objects.githubusercontent.com/x/jaco-dist-v9.9.9.zip', $releaseUrl));
        $this->assertFalse(is_release_asset_url('https://evil.com/jaco-dist-v0.2.0.zip?x=1', $releaseUrl));
        $this->assertFalse(is_release_asset_url('http://objects.githubusercontent.com/jaco-dist-v0.2.0.zip', $releaseUrl));
        $this->assertFalse(is_release_asset_url('https://objects.githubusercontent.com/x.zip', 'https://evil.com/jaco-dist-v0.2.0.zip'));
    }

    #[TestDox('The final URL of a redirected download is read from its headers')]
    public function testResponseFinalUrl(): void
    {
        $headers = [
            'HTTP/1.1 302 Found',
            'Location: https://objects.githubusercontent.com/asset/1/jaco-dist-v0.2.0.zip',
            'HTTP/1.1 200 OK',
            'Content-Type: application/zip',
        ];

        $this->assertSame(
            'https://objects.githubusercontent.com/asset/1/jaco-dist-v0.2.0.zip',
            response_final_url($headers),
        );

        // no redirect
        $this->assertNull(response_final_url(['HTTP/1.1 200 OK', 'Content-Length: 42']));

        // among several hops, the first Location is the one that names the asset
        // the release published; later hops are the CDN's own opaque redirects
        $this->assertSame(
            'https://objects.githubusercontent.com/jaco-dist-v0.2.0.zip',
            response_final_url([
                'HTTP/1.1 302 Found',
                'Location: https://objects.githubusercontent.com/jaco-dist-v0.2.0.zip',
                'HTTP/1.1 302 Found',
                'Location: https://cdn.example/opaque',
                'HTTP/1.1 200 OK',
            ]),
        );
    }

    #[TestDox('The checksum URL names the asset beside the archive')]
    public function testChecksumUrl(): void
    {
        // no redirect: a local archive, the checksum sits next to it
        $zipPath = $this->makeZip('sumurl.zip', ['jaco/api.php' => "<?php\n"]);

        $this->assertSame('file://' . $zipPath . '.sha256', checksum_url('file://' . $zipPath));

        // already a checksum URL: returned as it is, no second suffix
        $signed = 'https://objects.githubusercontent.com/a/jaco-dist-v0.2.0.zip.sha256?X-Amz-Signature=abc';
        $this->assertSame($signed, checksum_url($signed));

        @unlink($zipPath);
    }

    /**
     * @param callable(): string $checksum what the published checksum file contains
     */
    private function checksumArchive(string $zipPath, callable $checksum): void
    {
        // verify_release_checksum reads <url>.sha256, a local path maps to file://
        file_put_contents($zipPath . '.sha256', $checksum());
    }

    private function sha256Of(string $path): string
    {
        return (string) hash_file('sha256', $path);
    }

    /**
     * Runs the check and reports the message it throws, '' when it verified.
     *
     * @param callable(): void $verify
     */
    private function checksumError(callable $verify): string
    {
        try {
            $verify();
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        return '';
    }

    #[TestDox('A matching published checksum verifies the archive')]
    public function testChecksumAcceptsMatchingDigest(): void
    {
        $zipPath = $this->makeZip('signed.zip', ['jaco/api.php' => "<?php\n"]);
        $url = 'file://' . $zipPath;

        // sha256sum style, as build-dist.sh and the release workflow write it
        $this->checksumArchive($zipPath, fn(): string => $this->sha256Of($zipPath) . "  jaco-dist-v0.2.0.zip\n");
        $this->assertSame('', $this->checksumError(fn() => verify_release_checksum($url, $zipPath)), 'sha256sum format');

        // a bare digest and upper case hex are accepted as well
        $this->checksumArchive($zipPath, fn(): string => strtoupper($this->sha256Of($zipPath)));
        $this->assertSame('', $this->checksumError(fn() => verify_release_checksum($url, $zipPath)), 'bare upper case digest');

        @unlink($zipPath);
        @unlink($zipPath . '.sha256');
    }

    #[TestDox('A tampered archive or a missing checksum is refused')]
    public function testChecksumRejectsTamperedArchive(): void
    {
        $zipPath = $this->makeZip('tampered.zip', ['jaco/api.php' => "<?php\n"]);
        $url = 'file://' . $zipPath;
        $digest = $this->sha256Of($zipPath);

        // the archive changes after the checksum was published
        $this->checksumArchive($zipPath, fn(): string => $digest . "  jaco-dist-v0.2.0.zip\n");
        file_put_contents($zipPath, 'evil payload');

        try {
            verify_release_checksum($url, $zipPath);
            $this->fail('A checksum mismatch must not verify.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Checksum mismatch', $e->getMessage());
        }

        @unlink($zipPath);
        @unlink($zipPath . '.sha256');
    }

    #[TestDox('A release without a published checksum is refused')]
    public function testChecksumRejectsMissingFile(): void
    {
        $zipPath = $this->makeZip('nosum.zip', ['jaco/api.php' => "<?php\n"]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No checksum published');

        try {
            verify_release_checksum('file://' . $zipPath, $zipPath);
        } finally {
            @unlink($zipPath);
        }
    }

    #[TestDox('A malformed checksum file is refused')]
    public function testChecksumRejectsMalformedFile(): void
    {
        $zipPath = $this->makeZip('badsum.zip', ['jaco/api.php' => "<?php\n"]);
        $this->checksumArchive($zipPath, fn(): string => "not-a-digest  jaco-dist-v0.2.0.zip\n");

        try {
            verify_release_checksum('file://' . $zipPath, $zipPath);
            $this->fail('A malformed checksum file must not verify.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Malformed checksum', $e->getMessage());
        } finally {
            @unlink($zipPath);
            @unlink($zipPath . '.sha256');
        }
    }

    #[TestDox('A local archive installs without a release checksum')]
    public function testFileUrlSkipsChecksum(): void
    {
        $zipPath = $this->makeZip('local.zip', [
            'jaco/api.php' => "<?php\n",
            'jaco/autoload.php' => "<?php\n",
        ]);

        // no .sha256 next to it: file:// is the operator's own archive
        $payload = perform_update($this->config(), 'file://' . $zipPath, null, allowLocalFile: true);

        $this->assertTrue($payload->isSuccess);

        @unlink($zipPath);
    }
}

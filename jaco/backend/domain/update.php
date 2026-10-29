<?php declare(strict_types=1);

namespace jaco\domain\update;

use jaco\core\config\Config;
use jaco\core\filesystem;
use jaco\domain\Payload;

/**
 * Compare the installed version with the latest published release.
 *
 * @return Payload success: ['current' => string, 'latest' => string,
 *   'available' => bool, 'downloadUrl' => string, 'releaseUrl' => string]
 */
function check_update(Config $config): Payload
{
    $current = $config->appVersion;

    [$latest, $downloadUrl, $releaseUrl, $error] = fetch_latest_release();

    if ($error !== null) {
        return Payload::error([['', $error]]);
    }

    $available = version_compare(ltrim($latest, 'v'), ltrim($current, 'v'), '>');

    return Payload::success([
        'current' => $current,
        'latest' => $latest,
        'available' => $available,
        'downloadUrl' => $downloadUrl,
        'releaseUrl' => $releaseUrl,
    ]);
}

/**
 * Fetch the latest release metadata from GitHub.
 *
 * @return array{0: string, 1: string, 2: string, 3: string|null} latest tag,
 *   download url, release url, error message
 */
function fetch_latest_release(?string $tag = null): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Jaco-CMS/" . "updater\r\nAccept: application/vnd.github+json\r\n",
            'timeout' => 30,
        ],
    ]);

    if ($tag === null || $tag === '') {
        $url = 'https://api.github.com/repos/tbreuss/jaco-cms/releases/latest';
    } else {
        $url = 'https://api.github.com/repos/tbreuss/jaco-cms/releases/tags/' . rawurlencode($tag);
    }

    $json = @file_get_contents($url, false, $context);

    if ($json === false) {
        return ['', '', '', 'Could not reach GitHub.'];
    }

    $data = json_decode($json, true);

    if (!is_array($data) || !isset($data['tag_name'])) {
        return ['', '', '', 'Unexpected response from GitHub.'];
    }

    $tag = (string) $data['tag_name'];
    $releaseUrl = (string) ($data['html_url'] ?? '');

    $downloadUrl = '';
    foreach (($data['assets'] ?? []) as $asset) {
        if (is_array($asset) && isset($asset['name'], $asset['browser_download_url'])
            && preg_match('/^jaco-dist-.*\.zip$/', (string) $asset['name'])) {
            $downloadUrl = (string) $asset['browser_download_url'];
            break;
        }
    }

    if ($downloadUrl === '') {
        return [$tag, '', $releaseUrl, 'No dist archive found in the release.'];
    }

    return [$tag, $downloadUrl, $releaseUrl, null];
}

/**
 * Download and install an update.
 *
 * Replaces only the jaco/ application directory. Settings, backups, runtime,
 * templates, media and the public pages are left untouched.
 *
 * @return Payload success: ['from' => string, 'to' => string, 'backup' => string]
 */
function perform_update(Config $config, ?string $downloadUrl = null, ?string $targetVersion = null): Payload
{
    $appDir = $config->appDir;
    $webDir = $config->webDir;
    $backupDir = $config->backupDir;

    // resolve download url if not given
    if ($downloadUrl === null) {
        [$latest, $url, , $error] = fetch_latest_release($targetVersion);

        if ($error !== null) {
            return Payload::error([['', $error]]);
        }

        $downloadUrl = $url;
        $targetVersion = $latest;
    }

    if ($downloadUrl === '') {
        return Payload::invalid([['', 'No download URL.']]);
    }

    // ensure backup dir exists
    if (!is_dir($backupDir) && !filesystem\make_dir_rec($config, $webDir, 'jaco-data/backups')) {
        return Payload::error([['', 'Could not create backup directory.']]);
    }

    $tempDir = sys_get_temp_dir() . '/jaco-update-' . uniqid('', true);
    if (!mkdir($tempDir, 0755, true)) {
        return Payload::error([['', 'Could not create temp directory.']]);
    }

    $zipPath = $tempDir . '/release.zip';

    try {
        // download
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Jaco-CMS/updater\r\n",
                'timeout' => 300,
            ],
        ]);

        $data = @file_get_contents($downloadUrl, false, $context);

        if ($data === false) {
            throw new \RuntimeException('Download failed.');
        }

        if (file_put_contents($zipPath, $data) === false) {
            throw new \RuntimeException('Could not write download.');
        }

        // verify and extract zip
        $extractDir = $tempDir . '/extracted';

        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();

            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('Invalid ZIP archive.');
            }

            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new \RuntimeException('Could not extract archive.');
            }

            $zip->close();
        } elseif (function_exists('shell_exec')) {
            if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true)) {
                throw new \RuntimeException('Could not create extraction directory.');
            }

            $output = @shell_exec('unzip -q ' . escapeshellarg($zipPath) . ' -d ' . escapeshellarg($extractDir) . ' 2>&1');

            if (!file_exists($extractDir . '/jaco/api.php') && !file_exists($extractDir . '/api.php')) {
                throw new \RuntimeException('Could not extract archive: ' . trim((string) $output));
            }
        } else {
            throw new \RuntimeException('ZIP extension missing and no unzip binary available. Install the php-zip extension.');
        }

        // find the jaco/ directory in the extracted tree
        $sourceJaco = find_jaco_dir($extractDir);

        if ($sourceJaco === null) {
            throw new \RuntimeException('No jaco/ directory in archive.');
        }

        // backup current jaco/
        $backupName = 'jaco-' . preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $config->appVersion)
            . '-' . date('Ymd_His');

        $backupPath = $backupDir . '/' . $backupName;

        if (!copy_dir_rec($appDir, $backupPath)) {
            throw new \RuntimeException('Backup of jaco/ failed.');
        }

        // replace jaco/ contents
        // remove old contents (keep the dir itself)
        foreach (scandir($appDir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $appDir . '/' . $item;

            if (is_dir($path) && !is_link($path)) {
                remove_dir_rec($path);
            } else {
                @unlink($path);
            }
        }

        // copy new contents
        if (!copy_dir_rec($sourceJaco, $appDir)) {
            // attempt restore
            remove_dir_rec($appDir);
            @mkdir($appDir, 0755, true);
            copy_dir_rec($backupPath, $appDir);
            throw new \RuntimeException('Install failed, restored from backup.');
        }

        // verify
        if (!file_exists($appDir . '/api.php')) {
            remove_dir_rec($appDir);
            @mkdir($appDir, 0755, true);
            copy_dir_rec($backupPath, $appDir);
            throw new \RuntimeException('Verification failed, restored from backup.');
        }

        return Payload::success([
            'from' => $config->appVersion,
            'to' => $targetVersion ?? 'unknown',
            'backup' => $backupName,
        ]);
    } catch (\Throwable $e) {
        // cleanup temp
        remove_dir_rec($tempDir);
        return Payload::error([['', $e->getMessage()]]);
    } finally {
        if (is_dir($tempDir)) {
            remove_dir_rec($tempDir);
        }
    }
}

function find_jaco_dir(string $root): ?string
{
    if (is_dir($root . '/jaco') && file_exists($root . '/jaco/api.php')) {
        return $root . '/jaco';
    }

    // maybe extracted flat
    if (file_exists($root . '/api.php')) {
        return $root;
    }

    // search one level
    foreach (scandir($root) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $root . '/' . $item;

        if (is_dir($path) && file_exists($path . '/jaco/api.php')) {
            return $path . '/jaco';
        }
    }

    return null;
}

function copy_dir_rec(string $src, string $dst): bool
{
    if (!is_dir($src)) {
        return false;
    }

    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        return false;
    }

    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;

        if (is_dir($srcPath) && !is_link($srcPath)) {
            if (!copy_dir_rec($srcPath, $dstPath)) {
                return false;
            }
        } else {
            if (!copy($srcPath, $dstPath)) {
                return false;
            }
        }
    }

    return true;
}

function remove_dir_rec(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;

        if (is_dir($path) && !is_link($path)) {
            remove_dir_rec($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

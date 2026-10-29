<?php declare(strict_types=1);

namespace jaco\domain\update;

use jaco\core\config\Config;
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
    $backupDir = $config->appBackupDir;

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

    // validate the URL scheme: only HTTPS (or file:// for local testing)
    $scheme = (string) parse_url($downloadUrl, PHP_URL_SCHEME);

    if (!in_array($scheme, ['https', 'file'], true)) {
        return Payload::invalid([['', 'Download URL must use HTTPS.']]);
    }

    // for HTTPS, accept any host but require a .zip path
    if ($scheme === 'https') {
        $path = (string) parse_url($downloadUrl, PHP_URL_PATH);

        if (!str_ends_with(strtolower($path), '.zip')) {
            return Payload::invalid([['', 'Download URL must point to a .zip archive.']]);
        }
    }

    // reject downgrades: only same or newer versions are allowed
    if ($targetVersion !== null && $targetVersion !== '') {
        $current = ltrim($config->appVersion, 'v');
        $target = ltrim($targetVersion, 'v');

        if (version_compare($target, $current, '<')) {
            return Payload::invalid([['', 'Downgrade not allowed: ' . $target . ' is older than ' . $current . '.']]);
        }
    }

    // ensure backup dir exists (use the configured data dir, not a hardcoded path)
    if (!is_dir($backupDir) && !@mkdir($backupDir, $config->directoryPermissions, true)) {
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

        // backup current jaco/ as a directory copy
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

        // prune old app backups: keep only the 5 newest
        prune_app_backups($backupDir, 5);

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

function find_jaco_dir(string $root, int $depth = 0): ?string
{
    if ($depth > 5) {
        return null;
    }

    // direct jaco/ directory
    if (is_dir($root . '/jaco') && file_exists($root . '/jaco/api.php')) {
        return $root . '/jaco';
    }

    // extracted flat (root itself is jaco/)
    if (file_exists($root . '/api.php') && file_exists($root . '/autoload.php')) {
        return $root;
    }

    // recurse into subdirectories
    if (!is_dir($root)) {
        return null;
    }

    foreach (scandir($root) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $root . '/' . $item;

        if (is_dir($path) && !is_link($path)) {
            $found = find_jaco_dir($path, $depth + 1);

            if ($found !== null) {
                return $found;
            }
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

function prune_app_backups(string $dir, int $keep): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = [];

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;

        if (is_dir($path) && str_starts_with($item, 'jaco-')) {
            $mtime = @filemtime($path);
            $files[] = ['path' => $path, 'mtime' => $mtime === false ? 0 : $mtime, 'isDir' => true];
        }
    }

    if (count($files) <= $keep) {
        return;
    }

    usort($files, fn(array $a, array $b): int => $a['mtime'] <=> $b['mtime']);

    $toDelete = count($files) - $keep;

    for ($i = 0; $i < $toDelete; $i++) {
        remove_dir_rec($files[$i]['path']);
    }
}

<?php declare(strict_types=1);

namespace jaco\domain\update;

use jaco\core\config\Config;
use jaco\domain\Payload;

// A release archive is executed as code once it is installed: it replaces the
// whole application directory. So only an archive that comes from this project's
// GitHub release and matches its published checksum is installed. The rule lives
// in code, not in settings.php, because settings.php is writable from the admin.
const RELEASE_REPO = 'tbreuss/jaco-cms';

const RELEASE_ASSET_PATTERN = '/^jaco-dist-.+\.zip$/';


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
 * @param string|null $tag release tag such as "v0.2.0", never a branch or ref:
 *   the GitHub releases API resolves tags only
 *
 * @return array{0: string, 1: string, 2: string, 3: string|null} latest tag,
 *   download url, release url, error message
 */
function fetch_latest_release(?string $tag = null): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Jaco-CMS/updater\r\nAccept: application/vnd.github+json\r\n",
            'timeout' => 30,
        ],
    ]);

    if ($tag === null || $tag === '') {
        $url = 'https://api.github.com/repos/' . RELEASE_REPO . '/releases/latest';
    } else {
        // a ref such as "main" or "refs/heads/main" is not a release: the API
        // answers 404, which must not be reported as an unreachable host
        if (!is_release_tag($tag)) {
            return ['', '', '', 'Not a release tag: ' . $tag . ' (expected a version such as v0.2.0).'];
        }

        $url = 'https://api.github.com/repos/' . RELEASE_REPO . '/releases/tags/' . rawurlencode($tag);
    }

    /** @var array<int, string> $http_response_header */
    $http_response_header = [];
    $json = @file_get_contents($url, false, $context);

    if ($json === false) {
        $notFound = preg_match('#^HTTP/\S+\s+404\s#', $http_response_header[0] ?? '') === 1;

        return ['', '', '', $notFound
            ? 'No release ' . $tag . ' published in ' . RELEASE_REPO . '.'
            : 'Could not reach GitHub.'];
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
            && preg_match(RELEASE_ASSET_PATTERN, (string) $asset['name'])) {
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
 * Whether a value is a release tag (optionally prefixed with "v"), not a branch,
 * commit or nested ref. Without this check version_compare() treats a word as
 * older than any version, so "main" reads as a downgrade.
 */
function is_release_tag(string $tag): bool
{
    return preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:-[0-9A-Za-z.-]+)?$/', $tag) === 1;
}

/**
 * Whether a download URL points to the .zip asset of this project's GitHub release.
 */
function is_release_download_url(string $url): bool
{
    $scheme = parse_url($url, PHP_URL_SCHEME);
    $host = parse_url($url, PHP_URL_HOST);
    $path = (string) parse_url($url, PHP_URL_PATH);

    return strtolower((string) $scheme) === 'https'
        && strtolower((string) $host) === 'github.com'
        && preg_match(RELEASE_ASSET_PATTERN, basename($path)) === 1
        && str_contains($path, '/' . RELEASE_REPO . '/releases/download/');
}

/**
 * Last resort for a redirect: the CDN URL GitHub redirects a verified release asset
 * to. The path is opaque, so it is bound by the host and by the asset name it
 * carries; the checksum still has to match the bytes afterwards.
 */
function is_release_asset_url(string $url, string $releaseUrl): bool
{
    if (!is_release_download_url($releaseUrl)) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    $host = parse_url($url, PHP_URL_HOST);
    $path = (string) parse_url($url, PHP_URL_PATH);

    $host = strtolower((string) $host);

    // GitHub rotates the CDN hostname (objects.githubusercontent.com,
    // release-assets.githubusercontent.com, ...). Accept github.com and any
    // *.githubusercontent.com host so this check does not break on every
    // CDN change; the checksum verification below still guards the bytes.
    $hostAllowed = $host === 'github.com' || str_ends_with($host, '.githubusercontent.com');

    return strtolower((string) $scheme) === 'https'
        && $hostAllowed
        && basename($path) === basename((string) parse_url($releaseUrl, PHP_URL_PATH));
}

/**
 * Extract where a download ended up from the stream wrapper's response headers.
 * Every followed redirect appends another header block, the last Location wins.
 * Returns null when the request was not redirected.
 *
 * @param array<int, string> $headers $http_response_header of the download
 */
function response_final_url(array $headers): ?string
{
    $location = null;
    $block = -1;

    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\d#', $header) === 1) {
            $block++;
            continue;
        }

        if (preg_match('/^location:\s*(\S+)/i', $header, $matches) === 1 && $location === null) {
            $location = $matches[1];
        }
    }

    // a single header block is the final response: only more than one means the
    // stream wrapper followed a redirect
    return $block > 0 ? $location : null;
}

/**
 * The URL of the published checksum file: the asset URL with ".sha256" on the
 * path, before any query. Follows the redirect first, because a relative redirect
 * target cannot be requested again on its own.
 */
function checksum_url(string $downloadUrl): string
{
    if (str_ends_with((string) parse_url($downloadUrl, PHP_URL_PATH), '.sha256')) {
        return $downloadUrl;
    }

    $resolved = resolve_redirect($downloadUrl);
    $query = parse_url($resolved, PHP_URL_QUERY);
    $base = is_string($query) ? substr($resolved, 0, -strlen('?' . $query)) : $resolved;

    return $base . '.sha256' . (is_string($query) ? '?' . $query : '');
}

/**
 * Follow GitHub's redirect to the asset URL that can be fetched directly.
 * The stream wrapper reports relative targets as they come, and a relative URL
 * cannot be requested again (the checksum lives next to the archive).
 */
function resolve_redirect(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Jaco-CMS/updater\r\n",
            'timeout' => 30,
        ],
    ]);

    /** @var array<int, string> $http_response_header */
    $http_response_header = [];
    @file_get_contents($url, false, $context);
    $final = response_final_url($http_response_header);

    if ($final === null || $final === $url || preg_match('#^https?://#i', $final) === 1) {
        return $final ?? $url;
    }

    $parts = parse_url($url);

    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        return $url;
    }

    $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

    return str_starts_with($final, '//') ? $parts['scheme'] . ':' . $final
        : (str_starts_with($final, '/') ? $origin . $final : $origin . '/' . $final);
}

/**
 * Require the downloaded archive to match the <archive>.zip.sha256 file published
 * next to it in the same release. Without that file, or with a different digest,
 * the archive is not installed.
 *
 * @throws \RuntimeException when the checksum is missing or does not match
 */
function verify_release_checksum(string $downloadUrl, string $zipPath): void
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Jaco-CMS/updater\r\nAccept: */*\r\n",
            'timeout' => 30,
        ],
    ]);

    // the asset next to the archive, same release: <archive>.zip -> <archive>.zip.sha256
    $checksumUrl = checksum_url($downloadUrl);
    $text = @file_get_contents($checksumUrl, false, $context);

    if ($text === false || trim($text) === '') {
        throw new \RuntimeException(
            'No checksum published for this release. Refusing to install an unverified archive.',
        );
    }

    // accept "sha256sum" style ("<digest>  <file>") and a bare digest
    $matches = [];

    if (preg_match('/^([0-9a-fA-F]{64})(?:\s|$)/', trim($text), $matches) !== 1) {
        throw new \RuntimeException('Malformed checksum file for this release.');
    }

    $actual = hash_file('sha256', $zipPath);

    if ($actual === false || !hash_equals(strtolower($matches[1]), $actual)) {
        throw new \RuntimeException('Checksum mismatch: the downloaded archive is not the published release.');
    }
}

/**
 * Why self-updates are unavailable on this host, or null when they are available.
 *
 * zip is a suggested extension: without it Jaco runs, but an archive cannot be
 * extracted safely (the previous shell_exec/unzip fallback is deliberately gone).
 * extractor is injectable so the missing case is testable.
 *
 * @param callable(string): bool|null $extractor extension check, defaults to extension_loaded
 */
function missing_extractor_error(?callable $extractor = null): ?string
{
    $loaded = $extractor ?? extension_loaded(...);

    return $loaded('zip')
        ? null
        : 'The zip PHP extension is missing, so this installation cannot update itself. '
            . 'Ask your hoster to enable php-zip.';
}

/**
 * Extract a release archive into a directory. Only called once
 * missing_extractor_error confirmed ZipArchive is available.
 *
 * @throws \RuntimeException when the archive is unreadable or the extraction fails
 */
function extract_archive(string $zipPath, string $extractDir): void
{
    $zip = new \ZipArchive();

    if ($zip->open($zipPath) !== true) {
        throw new \RuntimeException('Invalid ZIP archive.');
    }

    if (!$zip->extractTo($extractDir)) {
        $zip->close();
        throw new \RuntimeException('Could not extract archive.');
    }

    $zip->close();
}

/**
 * Download and install an update.
 *
 * Replaces only the jaco/ application directory. Settings, backups, runtime,
 * templates, media and the public pages are left untouched.
 *
 * @param bool $allowLocalFile accept a file:// archive. Only the CLI sets it: in a
 *   web request the operator cannot vouch for a local file.
 *
 * @return Payload success: ['from' => string, 'to' => string, 'backup' => string]
 */
function perform_update(
    Config $config,
    ?string $downloadUrl = null,
    ?string $targetVersion = null,
    bool $allowLocalFile = false,
): Payload {
    $appDir = $config->appDir;
    $backupDir = $config->appBackupDir;

    // resolve download url if not given
    if ($downloadUrl === null) {
        [$latest, $url, , $error] = fetch_latest_release($targetVersion);

        if ($error !== null) {
            return Payload::error([['', $error]]);
        }

        // nothing to do if already up to date
        $current = ltrim($config->appVersion, 'v');
        $latestClean = ltrim($latest, 'v');

        if (version_compare($latestClean, $current, '<=')) {
            return Payload::success([
                'upToDate' => true,
                'from' => $config->appVersion,
                'to' => $latest,
                'backup' => '',
            ]);
        }

        $downloadUrl = $url;
        $targetVersion = $latest;
    }

    if ($downloadUrl === '') {
        return Payload::invalid([['', 'No download URL.']]);
    }

    // validate the URL scheme: only HTTPS in a web request. The CLI may also read
    // file:// archives, which is how an operator installs a release by hand.
    $scheme = (string) parse_url($downloadUrl, PHP_URL_SCHEME);
    $allowedSchemes = $allowLocalFile ? ['https', 'file'] : ['https'];

    if (!in_array($scheme, $allowedSchemes, true)) {
        return Payload::invalid([['', 'Download URL must use HTTPS.']]);
    }

    // a release is code: the host is fixed to the project's GitHub release, so a
    // URL from anywhere else never reaches the installer
    if ($scheme === 'https' && !is_release_download_url($downloadUrl)) {
        return Payload::invalid([['', 'Download URL must point to a ' . RELEASE_REPO . ' release.']]);
    }

    // reject downgrades: only same or newer versions are allowed
    if ($targetVersion !== null && $targetVersion !== '') {
        $current = ltrim($config->appVersion, 'v');
        $target = ltrim($targetVersion, 'v');

        if (version_compare($target, $current, '<')) {
            return Payload::invalid([['', 'Downgrade not allowed: ' . $target . ' is older than ' . $current . '.']]);
        }
    }

    // Installing needs an archive extractor. zip is a suggested extension, not a
    // required one: the rest of Jaco runs without it, so an installation on a host
    // without it keeps working and only installing an update is unavailable. The
    // request is validated first, so a bad request reports the bad request.
    if (($missing = missing_extractor_error()) !== null) {
        return Payload::error([['', $missing]]);
    }

    // ensure backup dir exists (use the configured data dir, not a hardcoded path)
    if (!is_dir($backupDir) && !mkdir($backupDir, $config->directoryPermissions, true)) {
        return Payload::error([['', 'Could not create backup directory.']]);
    }

    $tempDir = sys_get_temp_dir() . '/jaco-update-' . uniqid('', true);
    if (!mkdir($tempDir, $config->directoryPermissions, true)) {
        return Payload::error([['', 'Could not create temp directory.']]);
    }

    $zipPath = $tempDir . '/release.zip';

    try {
        // download. The stream wrapper follows GitHub's redirect to the CDN
        // itself; the first Location it reports names the asset and is what the
        // checksum check below is bound to.
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Jaco-CMS/updater\r\n",
                'timeout' => 300,
            ],
        ]);

        /** @var array<int, string> $http_response_header */
        $http_response_header = [];
        $data = @file_get_contents($downloadUrl, false, $context);
        $finalUrl = response_final_url($http_response_header);

        if ($data === false) {
            throw new \RuntimeException('Download failed.');
        }

        if (file_put_contents($zipPath, $data) === false) {
            throw new \RuntimeException('Could not write download.');
        }

        unset($data);

        // HTTPS: the archive must match the checksum the release publishes next to
        // it. A local file:// archive is trusted as it is, it is the operator's own.
        if ($scheme === 'https') {
            if ($finalUrl !== null && $finalUrl !== $downloadUrl) {
                if (!is_release_asset_url($finalUrl, $downloadUrl)) {
                    throw new \RuntimeException('Download was redirected away from the release.');
                }

                verify_release_checksum($finalUrl, $zipPath);
            } else {
                verify_release_checksum($downloadUrl, $zipPath);
            }
        }

        // verify and extract zip
        $extractDir = $tempDir . '/extracted';
        extract_archive($zipPath, $extractDir);

        // find the jaco/ directory in the extracted tree
        $sourceJaco = find_jaco_dir($extractDir);

        if ($sourceJaco === null) {
            throw new \RuntimeException('No jaco/ directory in archive.');
        }

        // backup current jaco/ as a directory copy
        $backupName = 'jaco-' . preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $config->appVersion)
            . '-' . date('Ymd_His');

        $backupPath = $backupDir . '/' . $backupName;

        // the application directory is replaced, never removed together with its
        // parent: a config with appDir at or above the web root would otherwise
        // take the pages and media with it
        if (is_same_or_ancestor($appDir, $config->webDir)) {
            throw new \RuntimeException(
                'Application directory contains the web root, refusing to replace it: ' . $appDir,
            );
        }

        if (!copy_dir_rec($appDir, $backupPath, $config->directoryPermissions)) {
            throw new \RuntimeException('Backup of jaco/ failed.');
        }

        // replace jaco/ contents
        // remove old contents (keep the dir itself). A target that cannot be
        // emptied completely is not mixed with the new version: that would leave
        // files of two releases side by side, so it counts as a failed install.
        $cleared = true;

        foreach (scandir($appDir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $appDir . '/' . $item;

            $removed = is_dir($path) && !is_link($path) ? remove_dir_rec($path) : unlink($path);
            $cleared = $removed && $cleared;
        }

        if (!$cleared) {
            restore_app_dir($appDir, $backupPath, $config);

            throw new \RuntimeException(
                is_dir($appDir) && file_exists($appDir . '/api.php')
                    ? 'Install failed, restored from backup.'
                    : 'Install failed and the restore failed too, the backup is kept in ' . $backupPath,
            );
        }

        // copy new contents
        if (!copy_dir_rec($sourceJaco, $appDir, $config->directoryPermissions)) {
            $restored = restore_app_dir($appDir, $backupPath, $config);

            throw new \RuntimeException(
                $restored && is_dir($appDir) && file_exists($appDir . '/api.php')
                    ? 'Install failed, restored from backup.'
                    : 'Install failed and the restore failed too, the backup is kept in ' . $backupPath,
            );
        }

        // verify
        if (!file_exists($appDir . '/api.php')) {
            $restored = restore_app_dir($appDir, $backupPath, $config);

            throw new \RuntimeException(
                $restored && is_dir($appDir) && file_exists($appDir . '/api.php')
                    ? 'Verification failed, restored from backup.'
                    : 'Verification failed and the restore failed too, the backup is kept in ' . $backupPath,
            );
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

/**
 * Whether $path is $ancestor itself or lies below it, after resolving "." and "..".
 * Used to refuse replacing a directory that contains the web root.
 */
function is_same_or_ancestor(string $ancestor, string $path): bool
{
    $normalize = static function (string $path): string {
        $parts = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($parts);
                continue;
            }

            $parts[] = $part;
        }

        return '/' . implode('/', $parts);
    };

    $ancestor = $normalize($ancestor);
    $path = $normalize($path);

    // "/" is above everything; for every other ancestor compare the component
    // lists, so "/srv/www" does not match "/srv/www2"
    if ($ancestor === '/') {
        return true;
    }

    return $path === $ancestor || str_starts_with($path, $ancestor . '/');
}

/**
 * Put the backed-up application directory back in place.
 *
 * The backup is copied next to the target first and only then swapped in, so a
 * failed copy still leaves a complete directory at $appDir. Deleting $appDir
 * before copying would leave the installation gone when the copy is interrupted,
 * which is exactly the situation this runs in.
 *
 * The staging directory sits next to the application, so it is inside the web root
 * whenever $appDir is. It is removed before reporting success, and a removal that
 * does not finish is reported as a failed restore: a leftover copy of the
 * application must not stay reachable over HTTP.
 */
function restore_app_dir(string $appDir, string $backupPath, Config $config): bool
{
    if (!is_dir($backupPath)) {
        return false;
    }

    $staging = $appDir . '.restore-' . uniqid('', true);

    try {
        if (!copy_dir_rec($backupPath, $staging, $config->directoryPermissions)) {
            remove_dir_rec($staging);

            return false;
        }

        // clear the target's contents, keep the directory itself: removing it
        // would also remove anything the operator placed around it
        if (!is_dir($appDir) && !mkdir($appDir, $config->directoryPermissions, true)) {
            remove_dir_rec($staging);

            return false;
        }

        $cleared = true;

        foreach (scandir($appDir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $appDir . '/' . $item;
            $removed = is_dir($path) && !is_link($path) ? remove_dir_rec($path) : unlink($path);

            // keep clearing: a leftover entry should not stop the rest
            $cleared = $removed && $cleared;
        }

        // a partly cleared target would be mixed with the restored files
        if (!$cleared || !copy_dir_rec($staging, $appDir, $config->directoryPermissions)) {
            remove_dir_rec($staging);

            return false;
        }

        // success only when the staging copy is gone as well
        return remove_dir_rec($staging);
    } catch (\Throwable) {
        remove_dir_rec($staging);

        return false;
    }
}

/**
 * Copy a directory tree. Source permissions are carried over: copy() creates the
 * target with 0666 & ~umask and loses the source mode, which would turn an
 * executable script into a plain file and silently widen a restricted one. The
 * backup has to restore what was there, so the mode travels with each entry.
 */
/**
 * Whether one of the existing directories above $path is a plain file, which makes
 * creating $path impossible. Walks up only until the first directory that exists,
 * so a deep missing path costs a handful of stat calls.
 */
function has_file_ancestor(string $path): bool
{
    $parent = dirname($path);

    while ($parent !== '' && $parent !== '.' && $parent !== '/' && !is_dir($parent)) {
        if (file_exists($parent)) {
            return true;
        }

        $parent = dirname($parent);
    }

    return false;
}

function copy_dir_rec(string $src, string $dst, int $permissions = 0755): bool
{
    if (!is_dir($src)) {
        return false;
    }

    // a plain file anywhere on the path where a directory belongs is a
    // deterministic conflict: report it instead of letting mkdir() warn and fail
    if ((file_exists($dst) && !is_dir($dst)) || has_file_ancestor($dst)) {
        return false;
    }

    if (!is_dir($dst)) {
        if (!mkdir($dst, $permissions, true)) {
            return false;
        }
    }

    foreach (scandir($src) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;

        if (is_dir($srcPath) && !is_link($srcPath)) {
            if (!copy_dir_rec($srcPath, $dstPath, $permissions)) {
                return false;
            }

            continue;
        }

        // same deterministic conflict for a file: the target path is taken by a
        // directory, copy() could only warn and fail
        if (is_dir($dstPath)) {
            return false;
        }

        if (!copy($srcPath, $dstPath)) {
            return false;
        }

        // the backup must carry the modes it saved: a silently ignored chmod
        // would leave 0644 behind, the very thing this function exists to avoid
        $sourceMode = fileperms($srcPath);

        if ($sourceMode !== false && !chmod($dstPath, $sourceMode & 0777)) {
            return false;
        }
    }

    // set the directory mode after filling it: a read-only source directory would
    // otherwise block the copies into it, and mkdir() applied the umask
    $sourceMode = fileperms($src);

    if ($sourceMode !== false && !chmod($dst, $sourceMode & 0777)) {
        return false;
    }

    return true;
}

/**
 * Remove a directory tree.
 *
 * @return bool true when nothing of $dir is left. A missing directory counts as
 *   removed, so the caller can tell an incomplete removal from a complete one:
 *   the update relies on an empty application directory before copying the new
 *   version in, and silently leaving content behind would corrupt the install.
 */
function remove_dir_rec(string $dir): bool
{
    if (!is_dir($dir)) {
        return true;
    }

    $complete = true;

    // a read-only directory cannot be emptied, and directories we own are not
    // read-only by intent: the backup carries the modes, so make it writable
    $mode = fileperms($dir);

    if ($mode !== false && ($mode & 0700) !== 0700) {
        chmod($dir, $mode | 0700);
    }

    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;

        if (is_dir($path) && !is_link($path)) {
            $complete = remove_dir_rec($path) && $complete;

            continue;
        }

        if ((is_file($path) || is_link($path)) && !unlink($path)) {
            $complete = false;
        }
    }

    if (is_dir($dir) && !rmdir($dir)) {
        $complete = false;
    }

    return $complete && !file_exists($dir);
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
            $mtime = filemtime($path);
            $files[] = ['path' => $path, 'mtime' => $mtime === false ? 0 : (int) $mtime, 'isDir' => true];
        }
    }

    if (count($files) <= $keep) {
        return;
    }

    usort($files, fn(array $a, array $b): int => $a['mtime'] <=> $b['mtime'] ?: strcmp($a['path'], $b['path']));

    $toDelete = count($files) - $keep;

    for ($i = 0; $i < $toDelete; $i++) {
        remove_dir_rec($files[$i]['path']);
    }
}

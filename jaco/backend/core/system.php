<?php declare(strict_types=1);

namespace jaco\core\system;

use jaco\core\config\Config;

function defined_constants(Config $config): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if (!str_starts_with($key, 'jaco\\')) {
            continue;
        }
        $constants[$key] = display_value($value, $config);
    }
    ksort($constants);
    return $constants;
}

function display_value(mixed $value, Config $config): mixed
{
    if (is_array($value)) {
        $value = json_encode($value);
    }
    if (is_string($value) && str_starts_with($value, $config->webDir)) {
        $value = substr($value, strlen($config->webDir));
    }
    return $value;
}

/**
 * The path as the owner knows it: relative to the web root, so the system page
 * does not print the account name and the directory layout of the hoster.
 * `jaco/vendor/autoload.php` instead of `/home/<user>/public_html/…/jaco/vendor/autoload.php`.
 * A path outside the web root stays as it is - there is nothing to cut off.
 */
function display_path(string $path, Config $config): string
{
    $webDir = rtrim($config->webDir, '/');

    if ($webDir === '' || $path === $webDir) {
        return $path;
    }

    if (str_starts_with($path, $webDir . '/')) {
        return substr($path, strlen($webDir) + 1);
    }

    return $path;
}

/**
 * Environment checks for the installer. Every entry reports one
 * requirement, the installer refuses to write settings while any
 * entry fails.
 *
 * @return list<array{label: string, ok: bool, detail: string}>
 */
function install_checks(Config $config): array
{
    // version_compare instead of PHP_VERSION_ID: the composer platform
    // pin only affects dependency resolution, the runtime PHP on a
    // shared hoster can still be older, so this check is real.
    $checks = [
        ['label' => 'PHP version', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'detail' => PHP_VERSION],
    ];

    foreach (['dom', 'libxml', 'openssl'] as $extension) {
        $checks[] = [
            'label' => 'PHP extension ' . $extension,
            'ok' => extension_loaded($extension),
            'detail' => extension_loaded($extension) ? 'loaded' : 'missing',
        ];
    }

    $vendor = $config->appDir . '/vendor/autoload.php';
    $checks[] = [
        'label' => 'Composer dependencies',
        'ok' => is_file($vendor),
        'detail' => display_path($vendor, $config),
    ];

    // the installer writes settings.php into the operator data: measuring the
    // application directory said "ok" while the write failed
    $checks[] = [
        'label' => 'Configuration writable',
        'ok' => is_writable($config->dataDir),
        'detail' => display_path($config->dataDir . '/settings.php', $config),
    ];

    foreach (
        [
            'Backup directory' => $config->backupDir,
            'Upload directory' => $config->mediaDir,
            'Log directory' => dirname($config->loginHistoryFile),
        ] as $label => $dir
    ) {
        // missing directories are fine when an existing parent is
        // writable, the installer creates them before writing settings
        $parent = $dir;
        while (!is_dir($parent) && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }
        $checks[] = ['label' => $label, 'ok' => is_writable($parent), 'detail' => display_path($dir, $config)];
    }

    return $checks;
}

/**
 * Hardening checks for the system page (not the installer): every entry
 * verifies that one .htaccess protection file exists. On Apache these files
 * block direct access to credentials, backups, logs and vendor code; on
 * nginx they are inert and the location blocks from nginx-jaco.conf.example
 * apply instead, so a missing file is a warning for the owner, never a
 * broken installation.
 *
 * @return list<array{label: string, ok: bool, detail: string}>
 */
function security_checks(Config $config): array
{
    $logDir = \dirname($config->loginHistoryFile);

    // Apache .htaccess wirkt rekursiv: Ein .htaccess in einem Elternverzeichnis
    // schützt auch alle Unterverzeichnisse. Für den Log-Schutz genügt daher ein
    // .htaccess irgendwo zwischen dem Log-Verzeichnis und der App (exkl. App).
    // Darüber hinaus liegt bereits die eigene App-Prüfung.
    $htaccessProtects = function (string $dir, string $stopAt): bool {
        while ($dir !== $stopAt && $dir !== \dirname($dir)) {
            if (\is_file($dir . '/.htaccess')) {
                return true;
            }
            $dir = \dirname($dir);
        }
        return false;
    };

    $files = [
        'App protection (.htaccess)' => $config->appDir . '/.htaccess',
        'Data protection (.htaccess)' => $config->dataDir . '/.htaccess',
        'Log protection (.htaccess)' => $logDir,
        'Vendor protection (.htaccess)' => $config->appDir . '/vendor/.htaccess',
    ];

    $checks = [];
    foreach ($files as $label => $check) {
        if ($label === 'Log protection (.htaccess)') {
            $ok = $htaccessProtects($check, $config->appDir);
            $detail = display_path($check . '/.htaccess', $config);
        } else {
            $ok = \is_file($check);
            $detail = display_path($check, $config);
        }
        $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
    }

    // Installer must not be reachable after setup
    $checks[] = [
        'label' => 'Installer removed',
        'ok' => !\is_file($config->appDir . '/install.php'),
        'detail' => display_path($config->appDir . '/install.php', $config),
    ];

    // settings.php must not be writable by the web user
    $settingsFile = $config->dataDir . '/settings.php';
    $checks[] = [
        'label' => 'Settings not writable',
        'ok' => !\is_file($settingsFile) || !\is_writable($settingsFile),
        'detail' => display_path($settingsFile, $config),
    ];

    // A short or missing secretKey is replaced by a per-request random
    // fallback, so the length is always valid. Check the source instead:
    // without a configured key, tokens do not survive the next request.
    $checks[] = [
        'label' => 'Secret key set',
        'ok' => $config->secretKeyIsConfigured,
        'detail' => $config->secretKeyIsConfigured
            ? \strlen($config->secretKey) . ' chars'
            : 'not configured (random fallback)',
    ];

    // Upload directory must exist and be writable
    $checks[] = [
        'label' => 'Upload directory writable',
        'ok' => \is_dir($config->mediaDir) && \is_writable($config->mediaDir),
        'detail' => display_path($config->mediaDir, $config),
    ];

    // Directory permissions should not be world-writable (0777)
    $dirsToCheck = [
        $config->mediaDir,
        $config->dataDir,
        $config->backupDir,
        \dirname($config->loginHistoryFile),
    ];
    $tooOpen = [];
    foreach ($dirsToCheck as $dir) {
        if (\is_dir($dir)) {
            $perms = \fileperms($dir) & 0777;
            if ($perms === 0777) {
                $tooOpen[] = display_path($dir, $config) . ' (' . \decoct($perms) . ')';
            }
        }
    }
    $checks[] = [
        'label' => 'Directory permissions not too open',
        'ok' => \count($tooOpen) === 0,
        'detail' => \count($tooOpen) > 0 ? \implode(', ', $tooOpen) : 'all ok',
    ];

    return $checks;
}

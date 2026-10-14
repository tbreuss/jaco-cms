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
    $checks[] = ['label' => 'Composer dependencies', 'ok' => is_file($vendor), 'detail' => $vendor];

    $checks[] = [
        'label' => 'Configuration writable',
        'ok' => is_writable($config->appDir),
        'detail' => $config->appDir . '/settings.php',
    ];

    foreach (
        [
            'Backup directory' => $config->backupDir,
            'Upload directory' => $config->uploadDir,
            'Log directory' => dirname($config->loginHistoryFile),
        ] as $label => $dir
    ) {
        // missing directories are fine when an existing parent is
        // writable, the installer creates them before writing settings
        $parent = $dir;
        while (!is_dir($parent) && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }
        $checks[] = ['label' => $label, 'ok' => is_writable($parent), 'detail' => $dir];
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

    $files = [
        'App protection (.htaccess)' => $config->appDir . '/.htaccess',
        'Backup protection (.htaccess)' => $config->backupDir . '/.htaccess',
        'Log protection (.htaccess)' => \dirname($logDir) . '/.htaccess',
        'Vendor protection (.htaccess)' => $config->appDir . '/vendor/.htaccess',
    ];

    $checks = [];
    foreach ($files as $label => $file) {
        $checks[] = ['label' => $label, 'ok' => \is_file($file), 'detail' => $file];
    }

    return $checks;
}

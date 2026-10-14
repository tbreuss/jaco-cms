<?php declare(strict_types=1);

namespace jaco\domain\system;

use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\system;
use jaco\core\totp;
use jaco\domain\Payload;

function authenticate_user(
    Config $config,
    string $username,
    string $withPassword,
    string $withTotpCode = '',
    string $address = '',
): Payload {
    $errors = data\validate(
        [
            'username' => $username,
            'withPassword' => $withPassword,
        ],
        [
            ['username', 'is_required'],
            ['username', 'is_string'],
            ['withPassword', 'is_required'],
            ['withPassword', 'is_string'],
        ],
    );

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    // strict: a blocked address is rejected without looking at credentials, and the
    // attempt counts again – so hammering keeps its own block alive, while every other
    // address (the owner's included) stays unaffected
    $blocked = auth\get_login_block_remaining($config, $address);

    if ($blocked > 0) {
        auth\record_login_failure($config, $address);
        $minutes = (int) ceil($blocked / 60);
        return Payload::invalid([['', 'Too many attempts. Try again in ' . $minutes . ' minute(s).']]);
    }

    $token = auth\authenticate($config, $username, $withPassword);

    if ($token === null) {
        auth\record_login_failure($config, $address);
        return Payload::invalid([['', 'Authentication failed.']]);
    }

    if ($config->totpSecret === '') {
        auth\record_login_success($config, $address);
        return Payload::success(['token' => $token]);
    }

    if ($withTotpCode === '') {
        return Payload::success(['totpRequired' => true]);
    }

    if (!totp\verify_code($config->totpSecret, $withTotpCode)) {
        auth\record_login_failure($config, $address);
        return Payload::invalid([['totpCode', 'Invalid authentication code.']]);
    }

    auth\record_login_success($config, $address);

    return Payload::success(['token' => $token]);
}

function get_environment(Config $config): Payload
{
    return Payload::success([
        'checks' => system\install_checks($config),
        'config' => display_config($config),
        'overview' => get_overview($config),
    ]);
}

/**
 * @return array<string, mixed>
 */
function display_config(Config $config): array
{
    $values = get_object_vars($config);
    $values['secretKey'] = '********';
    $values['password'] = '********';
    $values['totpSecret'] = $values['totpSecret'] === '' ? '' : '********';
    $values['directoryPermissions'] = sprintf('0%o', $config->directoryPermissions);
    $values['filePermissions'] = sprintf('0%o', $config->filePermissions);
    $values['maintenanceMode'] = $config->maintenanceMode ? 'true' : 'false';

    return $values;
}

/**
 * Operational overview for the system page. Read-only: unlike the
 * backup listing this does not prune expired backups.
 *
 * @return array{pages: int, uploads: int, backups: int, projectSize: int, logSize: int, logLines: int}
 */
function get_overview(Config $config): array
{
    [$logSize, $logLines] = log_stats($config->phpErrorLog);

    return [
        'pages' => count(filesystem\find_files_rec($config->webDir, $config->pageExtensions, [$config->appDir])),
        'uploads' => count(filesystem\find_files_rec($config->webDir, $config->uploadExtensions, [$config->appDir])),
        'backups' => count(filesystem\find_files($config->backupDir, array_merge($config->uploadExtensions, $config->pageExtensions))),
        'projectSize' => dir_size($config->webDir),
        'logSize' => $logSize,
        'logLines' => $logLines,
    ];
}

function dir_size(string $dir): int
{
    if (!\is_dir($dir) || !\is_readable($dir)) {
        return 0;
    }
    $size = 0;
    try {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    } catch (\UnexpectedValueException) {
        // unreadable subdirectory: count what was reachable
    }

    return $size;
}

function log_stats(string $file): array
{
    if (!\is_file($file)) {
        return [0, 0];
    }
    $size = \filesize($file);
    $lines = 0;
    $handle = \fopen($file, 'r');
    if ($handle !== false) {
        while (!\feof($handle)) {
            $chunk = \fread($handle, 8192);
            if ($chunk !== false) {
                $lines += substr_count($chunk, "\n");
            }
        }
        \fclose($handle);
    }

    return [$size === false ? 0 : $size, $lines];
}

function get_log_entries(Config $config, int $size = 100): Payload
{
    if ($size < 1) {
        return Payload::success([]);
    }

    $lines = [];
    $fp = \file_exists($config->phpErrorLog) ? \fopen($config->phpErrorLog, 'r') : false;
    if ($fp !== false) {
        $pos = -2; // Skip final new line character (Set to -1 if not present)

        $i = 0;
        $currentLine = '';

        while (-1 !== \fseek($fp, $pos, SEEK_END)) {
            $char = \fgetc($fp);
            if (PHP_EOL == $char) {
                $lines[] = $currentLine;
                $currentLine = '';
                $i++;
                if ($i >= $size) {
                    break;
                }
            } else {
                $currentLine = $char . $currentLine;
            }
            $pos--;
        }
        if (\trim($currentLine) !== '') {
            $lines[] = $currentLine; // Grab final line
        }
        \fclose($fp);
    }

    return Payload::success($lines);
}

function get_phpinfo(): Payload
{
    \ob_start();
    \phpinfo();
    $data = \ob_get_contents();
    \ob_clean();

    \preg_match("/<body[^>]*>(.*?)<\/body>/is", $data, $matches);

    if (!isset($matches[1])) {
        return Payload::error([['', 'Could not parse phpinfo output.']]);
    }

    return Payload::success($matches[1]);
}

function clear_error_log(Config $config): Payload
{
    if (!\file_exists($config->phpErrorLog)) {
        return Payload::success([]);
    }

    if (\file_put_contents($config->phpErrorLog, '') === false) {
        return Payload::error([['', 'Could not clear file.']]);
    }

    return Payload::success([]);
}

function get_version(Config $config): Payload
{
    return Payload::success([
        'name' => 'jaco',
        'version' => $config->appVersion,
    ]);
}

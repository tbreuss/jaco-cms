<?php

declare(strict_types=1);

namespace jaco\domain\system;

use jaco\auth;
use jaco\data;
use jaco\domain;
use jaco\system;

use const jaco\APP_VERSION;
use const jaco\ERROR_LOG;

/**
 * @return array{string, array}
 */
function authenticate(string $username, string $withPassword): array
{
    $errors = data\validate(
        [
            'username' => $username,
            'withPassword' => $withPassword,
        ],
        [
            ['username', 'required'],
            ['username', 'string'],
            ['withPassword', 'required'],
            ['withPassword', 'string'],
        ]
    );

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    $token = auth\authenticate($username, $withPassword);

    if ($token === null) {
        return domain\invalid([['', 'Authentication failed.']]);
    }

    return domain\success(['token' => $token]);
}

/**
 * @return array{string, array}
 */
function environment(): array
{
    return domain\success([
        system\defined_classes(),
        system\defined_constants(),
        system\defined_functions(),
    ]);
}

/**
 * @return array{string, array}
 */
function log(int $size = 100): array
{
    if ($size < 1) {
        return domain\success([]);
    }

    $lines = [];
    if (\file_exists(ERROR_LOG)) {
        $fp = \fopen(ERROR_LOG, 'r');

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

    return domain\success($lines);
}

/**
 * @return array{string, array}
 */
function phpinfo(): array
{
    \ob_start();
    \phpinfo();
    $data = \ob_get_contents();
    \ob_clean();

    \preg_match("/<body[^>]*>(.*?)<\/body>/is", $data, $matches);

    if (!isset($matches[1])) {
        return domain\error([['', 'Could not parse phpinfo output.']]);
    }

    return domain\success($matches[1]);
}

/**
 * @return array{string, array}
 */
function version(): array
{
    return domain\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

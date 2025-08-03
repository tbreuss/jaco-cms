<?php

declare(strict_types=1);

namespace jaco\domain\system;

use jaco\lib\auth;
use jaco\lib\data;
use jaco\lib\payload;
use jaco\lib\system;

use const jaco\APP_VERSION;
use const jaco\PHP_ERROR_LOG;

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
        return payload\invalid($errors);
    }

    $token = auth\authenticate($username, $withPassword);

    if ($token === null) {
        return payload\invalid([['', 'Authentication failed.']]);
    }

    return payload\success(['token' => $token]);
}

/**
 * @return array{string, array}
 */
function environment(): array
{
    return payload\success([
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
        return payload\success([]);
    }

    $lines = [];
    if (\file_exists(PHP_ERROR_LOG)) {
        $fp = \fopen(PHP_ERROR_LOG, 'r');

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

    return payload\success($lines);
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
        return payload\error([['', 'Could not parse phpinfo output.']]);
    }

    return payload\success($matches[1]);
}

/**
 * @return array{string, array}
 */
function version(): array
{
    return payload\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

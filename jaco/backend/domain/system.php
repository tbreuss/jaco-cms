<?php

declare(strict_types=1);

namespace jaco\domain\system;

use jaco\auth;
use jaco\data;
use jaco\domain;
use jaco\system;

use const jaco\APP_VERSION;
use const jaco\ERROR_LOG;

function authenticate(array $input): array
{
    $errors = data\validate($input, [
        ['username', 'required'],
        ['username', 'string'],
        ['password', 'required'],
        ['password', 'string'],
    ]);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    $token = auth\authenticate($input['username'], $input['password']);

    if ($token === null) {
        return domain\invalid([['', 'Authentication failed.']]);
    }

    return domain\success(['token' => $token]);
}

function environment(): array
{
    return domain\success([
        system\defined_classes(),
        system\defined_constants(),
        system\defined_functions(),
    ]);
}

function log(): array
{
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
                if ($i >= 1000) {
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

function phpinfo(): array
{
    \ob_start();
    \phpinfo();
    $data = \ob_get_contents();
    \ob_clean();

    \preg_match("/<body[^>]*>(.*?)<\/body>/is", $data, $matches);

    if (empty($matches[1])) {
        return domain\error([['', 'Could not parse phpinfo output.']]);
    }

    return domain\success($matches[1]);
}

function version(): array
{
    return domain\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

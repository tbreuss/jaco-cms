<?php

declare(strict_types=1);

namespace jaco\domain\system;

use jaco\lib\auth;
use jaco\lib\data;
use jaco\lib\system;
use const jaco\APP_VERSION;
use const jaco\PHP_ERROR_LOG;

final class DomainPayload extends \jaco\domain\Payload
{}

function authenticate_user(string $username, string $withPassword): DomainPayload
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
        return DomainPayload::invalid($errors);
    }

    $token = auth\authenticate($username, $withPassword);

    if ($token === null) {
        return DomainPayload::invalid([['', 'Authentication failed.']]);
    }

    return DomainPayload::success(['token' => $token]);
}

function get_environment(): DomainPayload
{
    return DomainPayload::success([
        system\defined_classes(),
        system\defined_constants(),
        system\defined_functions(),
    ]);
}

function get_log_entries(int $size = 100): DomainPayload
{
    if ($size < 1) {
        return DomainPayload::success([]);
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

    return DomainPayload::success($lines);
}

function get_phpinfo(): DomainPayload
{
    \ob_start();
    \phpinfo();
    $data = \ob_get_contents();
    \ob_clean();

    \preg_match("/<body[^>]*>(.*?)<\/body>/is", $data, $matches);

    if (!isset($matches[1])) {
        return DomainPayload::error([['', 'Could not parse phpinfo output.']]);
    }

    return DomainPayload::success($matches[1]);
}

function get_version(): DomainPayload
{
    return DomainPayload::success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

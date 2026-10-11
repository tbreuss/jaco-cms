<?php declare(strict_types=1);

namespace jaco\domain\system;

use jaco\core\auth;
use jaco\core\data;
use jaco\core\system;

use const jaco\APP_VERSION;
use const jaco\PHP_ERROR_LOG;

final class DomainPayload extends \jaco\domain\Payload {}

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
        ],
    );

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    // strict: a block rejects everything without looking at credentials.
    // blocked attempts still count, so persistent guessing reaches the
    // permanent block instead of stalling the counter at the temp limit.
    $blocked = auth\login_blocked();

    if ($blocked === PHP_INT_MAX) {
        auth\record_login_failure();
        return DomainPayload::invalid([['', 'Login blocked. Contact the administrator.']]);
    }

    if ($blocked > 0) {
        auth\record_login_failure();
        $minutes = (int) ceil($blocked / 60);
        return DomainPayload::invalid([['', 'Too many attempts. Try again in ' . $minutes . ' minute(s).']]);
    }

    $token = auth\authenticate($username, $withPassword);

    if ($token === null) {
        auth\record_login_failure();
        return DomainPayload::invalid([['', 'Authentication failed.']]);
    }

    auth\record_login_success();

    return DomainPayload::success(['token' => $token]);
}

function get_environment(): DomainPayload
{
    return DomainPayload::success([
        'classes' => system\defined_classes(),
        'constants' => system\defined_constants(),
        'functions' => system\defined_functions(),
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
        'version' => APP_VERSION,
    ]);
}

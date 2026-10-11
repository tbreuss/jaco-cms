<?php declare(strict_types=1);

namespace jaco\domain\system;

use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\system;
use jaco\domain\Payload;

function authenticate_user(Config $config, string $username, string $withPassword): Payload
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
        return Payload::invalid($errors);
    }

    // strict: a block rejects everything without looking at credentials.
    // blocked attempts still count, so persistent guessing reaches the
    // permanent block instead of stalling the counter at the temp limit.
    $blocked = auth\login_blocked($config);

    if ($blocked === PHP_INT_MAX) {
        auth\record_login_failure($config);
        return Payload::invalid([['', 'Login blocked. Contact the administrator.']]);
    }

    if ($blocked > 0) {
        auth\record_login_failure($config);
        $minutes = (int) ceil($blocked / 60);
        return Payload::invalid([['', 'Too many attempts. Try again in ' . $minutes . ' minute(s).']]);
    }

    $token = auth\authenticate($config, $username, $withPassword);

    if ($token === null) {
        auth\record_login_failure($config);
        return Payload::invalid([['', 'Authentication failed.']]);
    }

    auth\record_login_success($config);

    return Payload::success(['token' => $token]);
}

function get_environment(Config $config): Payload
{
    return Payload::success([
        'classes' => system\defined_classes(),
        'constants' => system\defined_constants($config),
        'functions' => system\defined_functions(),
    ]);
}

function get_log_entries(Config $config, int $size = 100): Payload
{
    if ($size < 1) {
        return Payload::success([]);
    }

    $lines = [];
    if (\file_exists($config->phpErrorLog)) {
        $fp = \fopen($config->phpErrorLog, 'r');

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

function get_version(Config $config): Payload
{
    return Payload::success([
        'name' => 'jaco',
        'version' => $config->appVersion,
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\error;

use jaco\response;
use jaco\emitter;

class Exception extends \Exception
{
}

class ActionNotFound extends Exception
{
}

class HttpUnauthorizedException extends Exception
{
}

// errors see https://jsonapi.org/format/#error-objects

function exception_handler(\Throwable $t): void
{
    $code = $t->getCode();

    $message = sprintf(
        '%s in %s on line %s',
        $t->getMessage(),
        $t->getFile(),
        $t->getLine()
    );

    error_log($message);

    emitter\emit(response\INTERNAL_SERVER_ERROR, [[
        'title' => get_error_type($t->getCode()),
        'detail' => $message
    ]]);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    $message = sprintf(
        '%s in %s on line %s',
        $errstr,
        $errfile,
        $errline
    );

    error_log($message);

    emitter\emit(response\INTERNAL_SERVER_ERROR, [[
        'title' => get_error_type($errno),
        'detail' => $message
    ]]);

    return true;
}

function fatal_handler(): void
{
    $error = error_get_last();
    if ($error !== null) {
        ob_clean();

        $message = sprintf(
            '%s in %s on line %s',
            $error['message'],
            $error['file'],
            $error['line']
        );

        error_log($message);

        emitter\emit(response\INTERNAL_SERVER_ERROR, [[
            'title' => get_error_type($error['type']),
            'detail' => $message
        ]]);
    }
}

function get_error_type(int $number): string
{
    return match ($number) {
        E_ERROR => "PHP Error",
        E_WARNING => "PHP Warning",
        E_PARSE => "PHP Parse Error",
        E_NOTICE => "PHP Notice",
        E_CORE_ERROR => "PHP Core Error",
        E_CORE_WARNING => "PHP Core Warning",
        E_COMPILE_ERROR => "PHP Compile Error",
        E_COMPILE_WARNING => "PHP Compile Warning",
        E_USER_ERROR => "PHP User Error",
        E_USER_WARNING => "PHP User Warning",
        E_USER_NOTICE => "PHP User Notice",
        E_STRICT => "PHP Strict Notice",
        E_RECOVERABLE_ERROR => "PHP Recoverable Error",
        E_DEPRECATED => "PHP Deprecated Warning",
        E_USER_DEPRECATED => "PHP User Deprecated Warning",
        default => "PHP Unknown Error",
    };
}

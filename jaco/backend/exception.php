<?php

declare(strict_types=1);

namespace jaco\exception;

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
    $status = $code > 0 ? $code : 500;

    $message = sprintf(
        '%s: %s in %s on line %s',
        get_error_type($t->getCode()),
        $t->getMessage(),
        $t->getFile(),
        $t->getLine()
    );

    error_log($message);

    emitter\emit($status, [[
        'code' => $t->getCode(),
        'detail' => $message
    ]]);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    $message = sprintf(
        '%s: %s in %s on line %s',
        get_error_type($errno),
        $errstr,
        $errfile,
        $errline
    );

    error_log($message);

    emitter\emit(500, [[
        'code' => get_error_type($errno),
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
            '%s: %s in %s on line %s',
            get_error_type($error['type']),
            $error['message'],
            $error['file'],
            $error['line']
        );

        error_log($message);

        emitter\emit(500, [[
            'code' => 500,
            'detail' => $message
        ]]);
    }
}

function get_error_type(int $number): string
{
    switch ($number) {
        case E_ERROR:
            return "PHP Error";
        case E_WARNING:
            return "PHP Warning";
        case E_PARSE:
            return "PHP Parse Error";
        case E_NOTICE:
            return "PHP Notice";
        case E_CORE_ERROR:
            return "PHP Core Error";
        case E_CORE_WARNING:
            return "PHP Core Warning";
        case E_COMPILE_ERROR:
            return "PHP Compile Error";
        case E_COMPILE_WARNING:
            return "PHP Compile Warning";
        case E_USER_ERROR:
            return "PHP User Error";
        case E_USER_WARNING:
            return "PHP User Warning";
        case E_USER_NOTICE:
            return "PHP User Notice";
        case E_STRICT:
            return "PHP Strict Notice";
        case E_RECOVERABLE_ERROR:
            return "PHP Recoverable Error";
        case E_DEPRECATED:
            return "PHP Deprecated Warning";
        case E_USER_DEPRECATED:
            return "PHP User Deprecated Warning";
        case E_ALL:
        default:
            return "PHP Unknown Error ($number)";
    }
}

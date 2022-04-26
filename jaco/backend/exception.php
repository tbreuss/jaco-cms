<?php

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

function exception_handler(\Throwable $t): void
{
    emitter\emit(500, [
        'code' => $t->getCode(),
        'type' => 'exception',
        'message' => $t->getMessage()
    ]);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline, array $errcontext = null): void
{
    emitter\emit(500, [
        'code' => $errno,
        'type' => 'error',
        'message' => $errstr
    ]);
}

function fatal_handler()
{
    $error = error_get_last();
    if ($error !== null) {
        emitter\emit(500, [
            'code' => 500,
            'type' => 'fatal',
            'message' => $error['message']
        ]);
        exit;
    }
}

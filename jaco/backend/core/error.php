<?php declare(strict_types=1);

namespace jaco\core\error;

use jaco\core\http\emitter;
use jaco\core\http\Status;

use function jaco\core\http\json_response;

class Exception extends \Exception {}

class ActionNotFound extends Exception {}

class HttpUnauthorizedException extends Exception {}

// errors see https://jsonapi.org/format/#error-objects
function exception_handler(\Throwable $t): void
{
    $message = format_error($t->getMessage(), $t->getFile(), $t->getLine());

    error_log($message);

    $response = json_response([
        [
            'title' => get_error_type($t->getCode()),
            'detail' => $message,
        ],
        Status::INTERNAL_SERVER_ERROR,
    ]);

    emitter\emit($response);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    $message = format_error($errstr, $errfile, $errline);

    error_log($message);

    $response = json_response([
        [
            'title' => get_error_type($errno),
            'detail' => $message,
        ],
        Status::INTERNAL_SERVER_ERROR,
    ]);

    emitter\emit($response, false);

    return true;
}

function fatal_handler(): void
{
    $error = error_get_last();
    if ($error !== null) {
        ob_clean();

        $message = format_error($error['message'], $error['file'], $error['line']);

        error_log($message);

        $response = json_response([
            [
                'title' => get_error_type($error['type']),
                'detail' => $message,
            ],
            Status::INTERNAL_SERVER_ERROR,
        ]);

        emitter\emit($response);
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
        2048 => "PHP Strict Notice", // we use an integer to prevent PHP Deprecated Warning in PHP 8.4
        E_RECOVERABLE_ERROR => "PHP Recoverable Error",
        E_DEPRECATED => "PHP Deprecated Warning",
        E_USER_DEPRECATED => "PHP User Deprecated Warning",
        default => "PHP Unknown Error",
    };
}

function format_error(string $message, string $file, int $line): string
{
    return sprintf('%s in %s on line %s', $message, $file, $line);
}

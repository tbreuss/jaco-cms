<?php declare(strict_types=1);

namespace jaco\core\error;

use jaco\core\http;
use jaco\core\http\Status;

use function jaco\core\http\json_response;

class Exception extends \Exception {}

class ActionNotFound extends Exception {}

class HttpUnauthorizedException extends Exception {}

// errors see https://jsonapi.org/format/#error-objects
function exception_handler(\Throwable $t): void
{
    // full detail goes to the server log only: the client gets a reference,
    // never paths or internals (the frontend shows the title anyway)
    $id = generate_reference();
    error_log($id . ' ' . format_error($t->getMessage(), $t->getFile(), $t->getLine()));

    $response = json_response([
        'title' => get_error_type($t->getCode()),
        'detail' => 'An unexpected error occurred (reference ' . $id . ').',
    ], Status::INTERNAL_SERVER_ERROR);

    http\emit($response);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    // skip errors that are suppressed with @ or not enabled in error_reporting
    if (!(error_reporting() & $errno)) {
        return false;
    }

    // log the error only, the response is emitted by the
    // exception handler or the fatal handler
    error_log(format_error($errstr, $errfile, $errline));

    return true;
}

function fatal_handler(): void
{
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    // only handle fatal errors, warnings and notices
    // are logged by the error handler
    $fatalErrors = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
    if (($error['type'] & $fatalErrors) === 0) {
        return;
    }

    if (ob_get_level() > 0) {
        ob_clean();
    }

    $message = format_error($error['message'], $error['file'], $error['line']);

    $id = generate_reference();
    error_log($id . ' ' . $message);

    $response = json_response([
        'title' => get_error_type($error['type']),
        'detail' => 'An unexpected error occurred (reference ' . $id . ').',
    ], Status::INTERNAL_SERVER_ERROR);

    http\emit($response);
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

/**
 * Short random reference linking a client response to its log entry,
 * so the admin finds the full detail in the error log by the reported id.
 */
function generate_reference(): string
{
    try {
        return substr(bin2hex(random_bytes(8)), 0, 12);
    } catch (\Throwable) {
        return (string) time();
    }
}

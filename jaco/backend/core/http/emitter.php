<?php declare(strict_types=1);

namespace jaco\core\http\emitter;

use jaco\core\http;
use jaco\core\http\Status;
use JetBrains\PhpStorm\NoReturn;

#[NoReturn]
function emit(http\Response $response): void
{
    ob_start();
    http_response_code($response->status->value);

    header_remove('x-powered-by');
    foreach ($response->headers as $name => $value) {
        header("$name: $value");
    }

    if (is_string($response->body)) {
        echo $response->body;
    }

    ob_end_flush();
    exit(0);
}

function send_errors(array $errors): string
{
    // keep errors consistent (like in json-api spec)
    foreach ($errors as $index => $error) {
        if (!isset($error['source'])) {
            $errors[$index]['source']['pointer'] = '';
        }
    }
    return _json_encode($errors);
}

function send_data(array $data): string
{
    return _json_encode($data);
}

function _json_encode(array $data): string
{
    $json = json_encode($data, JSON_PRESERVE_ZERO_FRACTION);
    if ($json === false) {
        http_response_code(Status::INTERNAL_SERVER_ERROR->value);
        return '[{"title":"Server Error","detail":"Could not JSON encode data"}]';
    }
    return $json;
}

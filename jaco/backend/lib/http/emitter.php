<?php

declare(strict_types=1);

namespace jaco\lib\http\emitter;

use jaco\lib\http\response;

function emit(int $status, string|array|null $response): void
{
    ob_start();
    http_response_code($status);
    header_remove('x-powered-by');
    if (is_string($response)) {
        echo $response;
    } elseif (is_array($response)) {
        header('Content-Type: application/json; charset=utf-8');
        if (response\is_2xx($status)) {
            echo send_data($response);
        } elseif (response\is_4xx($status)) {
            echo send_errors($response);
        } elseif (response\is_5xx($status)) {
            echo send_errors($response);
        }
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
        http_response_code(response\INTERNAL_SERVER_ERROR);
        return '[{"title":"Server Error","detail":"Could not JSON encode data"}]';
    }
    return $json;
}

<?php

declare(strict_types=1);

namespace jaco\http\emitter;

use jaco\http\response;

function emit(int $status, string|array|null $response): void
{
    ob_start();
    http_response_code($status);
    header_remove('x-powered-by');
    if (is_string($response)) {
        echo $response;
    } elseif (is_array($response)) {
        header('Content-Type: application/json; charset=utf-8');
        echo match ($status) {
            response\OK, response\CREATED => send_data($response),
            default => send_errors($response),
        };
    }
    ob_end_flush();
    exit;
}

function send_errors(array $errors): string
{
    // keep errors consistent (like in json-api spec)
    foreach ($errors as $index => $error) {
        if (!isset($error['source'])) {
            $errors[$index]['source']['pointer'] = '';
        }
    }
    return json_encode($errors);
}

function send_data(array $data): string
{
    $data = json_encode($data);
    if ($data === false) {
        throw new \exception('Could not encode json');
    }
    return $data;
}

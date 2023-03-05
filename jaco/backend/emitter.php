<?php

declare(strict_types=1);

namespace jaco\emitter;

use jaco\response;

/**
 * @phpstan-param string|array<mixed> $response
 */
function emit(int $status, string|array $response): void
{
    ob_start();
    http_response_code($status);
    if (is_string($response)) {
        echo $response;
    } elseif (is_array($response)) {
        header('Content-Type: application/json; charset=utf-8');
        echo match ($status) {
            response\OK => send_data($response),
            default => send_errors($response),
        };
    }
    ob_end_flush();
    exit;
}

/**
 * @phpstan-param array<mixed> $errors
 */
function send_errors(array $errors): string
{
    // keep errors consistent (like in json-api spec)
    foreach ($errors as $index => $error) {
        if (!isset($error['source'])) {
            $errors[$index]['source']['pointer'] = '';
        }
    }
    return json_encode(['errors' => $errors]);
}

/**
 * @phpstan-param array<mixed> $data
 */
function send_data(array $data): string
{
    $data = json_encode(['data' => $data]);
    if ($data === false) {
        throw new \exception('Could not encode json');
    }
    return $data;
}

<?php

namespace jaco\emitter;

function emit(int $status, $response)
{
    ob_start();
    http_response_code($status);
    if (is_string($response)) {
        echo $response;
    } elseif (is_array($response)) {
        header('Content-Type: application/json; charset=utf-8');
        if ($status >= 400) {
            echo send_errors($response);
        } else {
            echo send_data($response);
        }
    }
    ob_end_flush();
    exit;
}

function send_errors(array $errors): string
{
    return json_encode(['errors' => $errors]);
}

function send_data(array $data): string
{
    return json_encode(['data' => $data]);
}

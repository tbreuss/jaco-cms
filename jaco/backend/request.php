<?php

namespace jaco\request;

function post_values(): array
{
    $contents = file_get_contents('php://input');
    if (($contents === false) || (strlen($contents) === 0)) {
        return [];
    }
    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function get_header(string $header): ?string
{
    $headers = apache_request_headers();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === strtolower($header)) {
            return $value;
        }
    }
    return null;
}

<?php

namespace jaco\emitter;

function emit(int $status, $response)
{
    http_response_code($status);
    if (is_string($response)) {
        echo $response;
    } elseif (is_array($response)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }
}

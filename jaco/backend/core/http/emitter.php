<?php declare(strict_types=1);

namespace jaco\core\http\emitter;

use jaco\core\http;

function emit(http\Response $response, bool $exit = true): void
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

    if ($exit) {
        exit(0);
    }
}

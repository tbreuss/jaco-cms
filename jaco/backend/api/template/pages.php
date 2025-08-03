<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(): array
{
    $payload = domain\template\page_templates([]);

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === payload\SUCCESS) {
        return response\ok(
            array_map(fn(array $item): array => [
                'file' => $item['file'],
                'title' => $item['title'],
                'created' => $item['created'],
            ], $data)
        );
    }

    return responder\api_errors($payload);
}

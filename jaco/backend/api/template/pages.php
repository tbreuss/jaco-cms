<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\api\responder;
use jaco\domain;
use jaco\http\response;

function action(): array
{
    $payload = domain\template\page_templates([]);

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok(
            array_map(fn(array $item): array => [
                'file' => $item['file'],
                'title' => $item['title'],
                'created' => $item['created'],
            ], $data)
        );
    }

    return responder\errors($payload);
}

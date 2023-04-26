<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\api\responder;
use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\struct\page;

#[attribute\validate_response('/api/template/response.json')]
function action(): array
{
    $payload = domain\template\page\find_all([]);

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok(
            array_map(fn(page $item): array => [
                'file' => $item->file,
                'title' => $item->title,
                'created' => $item->created,
            ], $data)
        );
    }

    return responder\api_errors($payload);
}

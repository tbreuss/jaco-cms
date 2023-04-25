<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;
use jaco\struct\page;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/template/response.json')]
function action(): array
{
    $payload = domain\template\page\find_all([]);

    return responder($payload);
}

function responder($payload): array
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

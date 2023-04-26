<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_response('/api/index/response.json')]
function action(): array
{
    $payload = domain\system\version();

    return responder($payload);
}

function responder(array $payload): array
{
    [, $data] = $payload;

    return response\ok([
        'name' => $data['name'],
        'version' => $data['version'],
    ]);
}

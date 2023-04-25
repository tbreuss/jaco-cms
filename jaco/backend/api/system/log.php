<?php

declare(strict_types=1);

namespace jaco\api\system\log;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_response('/api/diagnostics/log/response.json')]
function action(): array
{
    $payload = domain\system\log();

    return responder($payload);
}

function responder($payload): array
{
    [, $data] = $payload;

    return response\ok($data);
}

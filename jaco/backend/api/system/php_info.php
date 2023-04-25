<?php

declare(strict_types=1);

namespace jaco\api\system\php_info;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

#[attribute\validate_response('/api/diagnostics/php_info/response.json')]
function action(): array
{
    $payload = domain\system\phpinfo();

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok([
            'content' => $data,
        ]);
    }

    return responder\api_errors($payload);
}

<?php

declare(strict_types=1);

namespace jaco\api\system\phpinfo;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(): array
{
    $payload = domain\system\phpinfo();

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === payload\SUCCESS) {
        return response\ok([
            'content' => $data,
        ]);
    }

    return responder\api_errors($payload);
}

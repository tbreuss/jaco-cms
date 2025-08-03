<?php

declare(strict_types=1);

namespace jaco\api\system\phpinfo;

use jaco\api\responder;
use jaco\domain;
use jaco\lib\http\response;

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

    return responder\errors($payload);
}

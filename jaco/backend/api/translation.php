<?php

declare(strict_types=1);

namespace jaco\api\translation;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(string $language): array
{
    $payload = domain\translation\load_all(
        forLanguage: $language
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === payload\SUCCESS) {
        return response\ok($data);
    }

    return responder\api_errors($payload);
}

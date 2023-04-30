<?php

declare(strict_types=1);

namespace jaco\api\translation;

use jaco\api\responder;
use jaco\domain;
use jaco\http\response;

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

    if ($status === domain\SUCCESS) {
        return response\ok($data);
    }

    return responder\api_errors($payload);
}

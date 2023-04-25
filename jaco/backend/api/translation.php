<?php

declare(strict_types=1);

namespace jaco\api\translation;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/translation/response.json')]
function action(string $language): array
{
    $payload = domain\translation\all($language);

    return responder($payload);
}

function responder($payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok($data);
    }

    return responder\api_errors($payload);
}

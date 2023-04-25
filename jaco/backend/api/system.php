<?php

declare(strict_types=1);

namespace jaco\api\system;

use jaco\attribute;
use jaco\http\response;
use jaco\domain;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/system/response.json')]
function action(): array
{
    $payload = domain\system\environment();

    return responder($payload);
}

function responder($payload): array
{
    [, $data] = $payload;
    [$classes, $constants, $functions] = $data;

    return response\ok([
        'classes' => $classes,
        'constants' => $constants,
        'functions' => $functions,
    ]);
}

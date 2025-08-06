<?php

declare(strict_types=1);

namespace jaco\api\system;

use jaco\domain\system;
use jaco\lib\http\response;

function action(): array
{
    $payload = system\environment();

    return responder($payload);
}

function responder(system\DomainPayload $payload): array
{
    [$classes, $constants, $functions] = $payload->data;

    return response\ok([
        'classes' => $classes,
        'constants' => $constants,
        'functions' => $functions,
    ]);
}

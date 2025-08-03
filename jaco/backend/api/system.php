<?php

declare(strict_types=1);

namespace jaco\api\system;

use jaco\domain;
use jaco\lib\http\response;

function action(): array
{
    $payload = domain\system\environment();

    return responder($payload);
}

function responder(array $payload): array
{
    [, $data] = $payload;
    [$classes, $constants, $functions] = $data;

    return response\ok([
        'classes' => $classes,
        'constants' => $constants,
        'functions' => $functions,
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\system;

use jaco\domain\system\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\system\get_environment;
use function jaco\lib\http\json_response;

function action(): Response
{
    $payload = get_environment();

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    [$classes, $constants, $functions] = $payload->data;

    return json_response([
        'classes' => $classes,
        'constants' => $constants,
        'functions' => $functions,
    ]);
}

<?php declare(strict_types=1);

namespace jaco\api\system;

use jaco\core\http\Response;
use jaco\domain\system\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\domain\system\get_environment;

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

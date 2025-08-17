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
    return json_response([
        'classes' => $payload->data['classes'],
        'constants' => $payload->data['constants'],
        'functions' => $payload->data['functions'],
    ]);
}

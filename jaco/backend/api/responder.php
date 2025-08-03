<?php

declare(strict_types=1);

namespace jaco\api\responder;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\responder;

function errors(array $payload, array $mapping = []): array
{
    [$status, $data] = $payload;

    if ($status === domain\INVALID) {
        $output = responder\transform_client_errors($data, $mapping);
        return response\unprocessable_entity($output);
    }

    if ($status === domain\ERROR) {
        $output = responder\transform_server_errors($data, $mapping);
        return response\internal_server_error($output);
    }

    return response\internal_server_error(
        responder\invalid_service_status()
    );
}

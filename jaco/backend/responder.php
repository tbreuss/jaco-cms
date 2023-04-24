<?php

declare(strict_types=1);

namespace jaco\responder;

use jaco\controller;
use jaco\domain;
use jaco\http\response;

function api_errors(array $payload, array $mapping = []): array
{
    [$status, $data] = $payload;

    if ($status === domain\INVALID) {
        $output = controller\transform_client_errors($data, $mapping);
        return response\unprocessable_entity($output);
    }

    if ($status === domain\ERROR) {
        $output = controller\transform_server_errors($data, $mapping);
        return response\internal_server_error($output);
    }

    return response\internal_server_error(
        controller\invalid_service_status()
    );
}

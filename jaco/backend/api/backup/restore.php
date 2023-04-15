<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\response;
use jaco\service;
use jaco\validate;

const input_mapping = [
    'file' => 'file',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/backup/restore/request.json')]
#[validate\response('/api/null.json')]
function action(array $apiInput): array
{
    #return service\call_from_api('jaco\service\backup\restore', $apiInput, input_mapping);

    $serviceInput = service\transform_data($apiInput, input_mapping);

    [$serviceStatus, $serviceOutput] = service\backup\restore($serviceInput);

    if ($serviceStatus === service\backup\SUCCESS) {
        return response\ok();
    }

    if ($serviceStatus === service\backup\INVALID) {
        $apiOutput = service\transform_errors($serviceOutput, array_flip(input_mapping));
        return response\unprocessable_entity($apiOutput);
    }

    if ($serviceStatus === service\backup\ERROR) {
        $apiOutput = service\transform_errors($serviceOutput, array_flip(input_mapping));
        return response\internal_server_error($apiOutput);
    }

    return response\internal_server_error(service\invalid_service_status());
}

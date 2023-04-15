<?php

declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\response;
use jaco\service;
use jaco\validate;

const input_mapping = [
    'file' => 'file',
];

const output_mapping = [
    'file' => 'file',
    'name' => 'name',
    'title' => 'title',
    'created' => 'created',
    'backedUp' => 'backedUp',
    'type' => 'type',
    'content' => 'content'
];

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\response('/api/backup/view/response.json')]
function action(string $file): array
{
    return service\call_from_api('jaco\service\backup\find_one', ['file' => $file], input_mapping, output_mapping);

    $serviceInput = service\transform_data(['file' => $file], input_mapping);

    [$serviceStatus, $serviceOutput] = service\backup\find_one($serviceInput);

    if ($serviceStatus === service\backup\SUCCESS) {
        $serviceOutput = service\transform_data($serviceOutput, output_mapping);
        return response\ok($serviceOutput);
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

<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\response;
use jaco\service;
use jaco\validate;

const input_mapping = [
    'srcFileName' => 'srcFile',
    'dstFileName' => 'dstFile',
    'dstPageTitle' => 'dstTitle'
];
const output_mapping = [
    'file' => 'file',
    'title' => 'title',
    'created' => 'created',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/copy/request.json')]
#[validate\response('/api/page/copy/response.json')]
function action(array $apiInput): array
{
    $serviceInput = service\transform_data($apiInput, input_mapping);

    [$serviceStatus, $serviceOutput] = service\page\copy($serviceInput);

    if ($serviceStatus === service\page\SUCCESS) {
        $apiOutput = service\transform_data($serviceOutput, output_mapping);
        return response\created($apiOutput);
    }

    if ($serviceStatus === service\page\INVALID) {
        $apiOutput = service\transform_errors($serviceOutput, array_flip(input_mapping));
        return response\unprocessable_entity($apiOutput);
    }

    if ($serviceStatus === service\page\ERROR) {
        $apiOutput = service\transform_errors($serviceOutput, array_flip(input_mapping));
        return response\internal_server_error($apiOutput);
    }

    return response\internal_server_error(service\invalid_service_status());
}

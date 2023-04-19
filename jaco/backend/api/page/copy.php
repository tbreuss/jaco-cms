<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const inputMapping = [
    'srcFileName' => 'srcFile',
    'dstFileName' => 'dstFile',
    'dstPageTitle' => 'dstTitle'
];
const outputMapping = [
    'file' => 'file',
    'title' => 'title',
    'created' => 'created',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/page/copy/request.json')]
#[attribute\validate_response('/api/page/copy/response.json')]
function action(array $apiInput): array
{
    $serviceInput = controller\transform_data($apiInput, inputMapping);

    [$serviceStatus, $serviceOutput] = service\page\copy($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, outputMapping);
        return response\created($apiOutput);
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

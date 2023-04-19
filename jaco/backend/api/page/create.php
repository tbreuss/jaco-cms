<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const inputMapping = [
    'fileName' => 'dstFile',
    'pageTitle' => 'dstTitle',
    'pageTemplate' => 'srcFile'
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/page/create/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $apiInput): array
{
    $serviceInput = controller\transform_data($apiInput, inputMapping);

    [$serviceStatus, $serviceOutput] = service\page\create_from_template($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        return response\created();
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

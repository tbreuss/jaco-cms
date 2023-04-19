<?php

declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const inputMapping = [
    'file' => 'file',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/backup/delete/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $apiInput): array
{
    $serviceInput = controller\transform_data($apiInput, inputMapping);

    [$serviceStatus, $serviceOutput] = service\backup\delete($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        return response\ok();
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

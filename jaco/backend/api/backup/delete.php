<?php

declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\domain;

const inputMapping = [
    'file' => 'file',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/backup/delete/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $bodyParams): array
{
    $serviceInput = controller\transform_data($bodyParams, inputMapping);

    [$serviceStatus, $serviceOutput] = domain\backup\delete($serviceInput);

    if ($serviceStatus === domain\SUCCESS) {
        return response\ok();
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

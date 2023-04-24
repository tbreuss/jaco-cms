<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

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
#[attribute\validate_request('/api/backup/restore/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $bodyParams): array
{
    $serviceInput = controller\transform_data($bodyParams, inputMapping);

    [$serviceStatus, $serviceOutput] = domain\backup\restore($serviceInput);

    if ($serviceStatus === domain\SUCCESS) {
        return response\ok();
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

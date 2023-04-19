<?php

declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const inputMapping = [
    'filter' => 'filter',
    'sort' => 'sort',
];

const outputMapping = [
    'items',
    'filter',
    'sort',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/backup/list/response.json')]
function action(string $filter = '', string $sort = '-created'): array
{
    $apiInput = ['filter' => $filter, 'sort' => $sort];
    $serviceInput = controller\transform_data($apiInput, inputMapping);

    [$serviceStatus, $serviceOutput] = service\backup\find_all($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, outputMapping);
        return response\ok($apiOutput);
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

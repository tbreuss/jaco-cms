<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const input_mapping = [
    'filter' => 'filter',
    'sort' => 'sort',
];

const output_mapping = [
    'items',
    'filter',
    'sort',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/upload/list/response.json')]
function action(string $filter = '', string $sort = '-created'): array
{
    $apiInput = ['filter' => $filter, 'sort' => $sort];
    $serviceInput = controller\transform_data($apiInput, input_mapping);

    [$serviceStatus, $serviceOutput] = service\upload\find_all($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, output_mapping);
        return response\ok($apiOutput);
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, input_mapping);
}

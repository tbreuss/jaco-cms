<?php

declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const input_mapping = [
    'file' => 'file',
];

const output_mapping = [
    'file' => 'file',
    'title' => 'title',
    'created' => 'created',
    'content' => 'content',
];

/**
 * @phpstan-return array{0:int, 1?:array{file: string, title: string, created:int, content: ?string}}
 */
#[attribute\validate_response('/api/page/view/response.json')]
function action(string $file): array
{
    $serviceInput = controller\transform_data(['file' => $file], input_mapping);

    [$serviceStatus, $serviceOutput] = service\page\find_one($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, output_mapping);
        return response\ok($apiOutput);
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, input_mapping);
}

<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;
use jaco\struct\page;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/template/response.json')]
function action(): array
{
    [$serviceStatus, $serviceOutput] = service\template\page\find_all([]);

    if ($serviceStatus === service\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, 'jaco\api\template\pages\_output_mapping');
        return response\ok($apiOutput);
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, []);
}

function _output_mapping(array $data): array
{
    return array_map(function(page $item): array {
        return [
            'file' => $item->file,
            'title' => $item->title,
            'created' => $item->created,
        ];
    }, $data);
}

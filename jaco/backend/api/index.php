<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

const outputMapping = [
    'name' => 'name',
    'version' => 'version',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/index/response.json')]
function action(): array
{
    [, $serviceOutput] = service\system\version();

    $apiOutput = controller\transform_data($serviceOutput, outputMapping);

    return response\ok($apiOutput);
}

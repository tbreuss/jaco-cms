<?php

declare(strict_types=1);

namespace jaco\api\system;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\domain;

const outputMapping = [
    'classes',
    'constants',
    'functions',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/system/response.json')]
function action(): array
{
    [, $serviceOutput] = domain\system\environment();

    $apiOutput = controller\transform_data($serviceOutput, outputMapping);

    return response\ok($apiOutput);
}

<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\domain;

const inputMapping = [
    'username' => 'username',
    'password' => 'password',
];

const outputMapping = [
    'token' => 'token',
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/login/request.json')]
#[attribute\validate_response('/api/login/response.json')]
function action(array $bodyParams): array
{
    $serviceInput = controller\transform_data($bodyParams, inputMapping);

    [$serviceStatus, $serviceOutput] = domain\system\authenticate($serviceInput);

    if ($serviceStatus === domain\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, outputMapping);
        return response\ok($apiOutput);
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);
}

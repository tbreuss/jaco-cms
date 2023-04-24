<?php

declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\domain;

const inputMapping = [
    'file' => 'file',
];

const outputMapping = [
    'file' => 'file',
    'name' => 'name',
    'title' => 'title',
    'created' => 'created',
    'backedUp' => 'backedUp',
    'type' => 'type',
    'content' => 'content'
];

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/backup/view/response.json')]
function action(string $file): array
{
    #return controller\call_service_from_api('jaco\service\backup\find_one', ['file' => $file], inputMapping, outputMapping);

    $serviceInput = controller\transform_data(['file' => $file], inputMapping);

    [$serviceStatus, $serviceOutput] = domain\backup\find_one($serviceInput);

    if ($serviceStatus === domain\SUCCESS) {
        $apiOutput = controller\transform_data($serviceOutput, outputMapping);
        return response\ok($apiOutput);
    }

    #return controller\handle_api_errors($serviceStatus, $serviceOutput, inputMapping);

    if ($serviceStatus === domain\INVALID) {
        $apiOutput = controller\transform_client_errors($serviceOutput, array_flip(inputMapping));
        return response\unprocessable_entity($apiOutput);
    }

    if ($serviceStatus === domain\ERROR) {
        $apiOutput = controller\transform_server_errors($serviceOutput, array_flip(inputMapping));
        return response\internal_server_error($apiOutput);
    }

    return response\internal_server_error(controller\invalid_service_status());
}

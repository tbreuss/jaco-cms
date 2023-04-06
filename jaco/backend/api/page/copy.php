<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\json;
use jaco\request;
use jaco\response;
use jaco\service\page;

/**
 * @phpstan-return array<int,mixed>
 */
//#[\json_schema_request('schema/page/copy_request.json')]
//#[\json_schema_response('schema/page/copy_response.json')]
function action(): array
{
    $data = request\get_post_values();

    $errors = json\validate_data($data, 'schema/api/page/copy/request.json');

    if (count($errors) > 0) {
        return response\unprocessable_entity(
            $errors
        );
    }

    [$status, $message] = page\copy(
        $data['srcFileName'],
        $data['dstFileName'],
        $data['dstPageTitle']
    );

    if ($status) {
        return response\ok();
    }

    return response\unprocessable_entity(
        [['detail' => $message, 'title' => 'Validation Error']]
    );
}

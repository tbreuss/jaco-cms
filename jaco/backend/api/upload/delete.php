<?php declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\upload\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\upload\delete_upload;

function action(Request $request): Response
{
    if (($blocked = maintenance_response()) !== null) {
        return $blocked;
    }

    $payload = delete_upload(
        file: $request->post->val('fileName'),
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'fileName',
    ]);
}

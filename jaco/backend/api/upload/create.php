<?php declare(strict_types=1);

namespace jaco\api\upload\create;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\upload\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\upload\create_upload;

function action(Request $request): Response
{
    if (($blocked = maintenance_response()) !== null) {
        return $blocked;
    }

    // multipart uploads are not JSON, the file comes from $_FILES
    $file = $_FILES['file'] ?? [];

    $payload = create_upload(
        file: is_array($file) ? $file : [],
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'file' => $payload->data['file'],
        ]);
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

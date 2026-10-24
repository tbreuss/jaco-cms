<?php declare(strict_types=1);

namespace jaco\api\upload\create;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\upload\create_upload;

function action(Config $config): Response
{

    // multipart uploads are not JSON, the file comes from $_FILES
    $file = $_FILES['file'] ?? [];

    $payload = create_upload(
        $config,
        file: is_array($file) ? $file : [],
    );

    return responder($payload);
}

function responder(Payload $payload): Response
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

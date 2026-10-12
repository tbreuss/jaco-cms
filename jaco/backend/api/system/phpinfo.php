<?php declare(strict_types=1);

namespace jaco\api\system\phpinfo;

use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\system\get_phpinfo;

function action(): Response
{
    $payload = get_phpinfo();

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'content' => $payload->data,
        ]);
    }

    return api_errors($payload);
}

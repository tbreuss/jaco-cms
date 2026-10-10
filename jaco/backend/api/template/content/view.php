<?php declare(strict_types=1);

namespace jaco\api\template\content\view;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\template\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\find_content_template;

function action(Request $request): Response
{
    $payload = find_content_template(
        file: $request->query->strVal('file'),
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'file' => $payload->data['file'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
            'content' => $payload->data['content'],
        ]);
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

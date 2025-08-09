<?php declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\domain\page\DomainPayload;
use jaco\core\http\Response;

use function jaco\domain\page\find_page;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(string $file): Response
{
    $payload = find_page(
        file: $file,
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

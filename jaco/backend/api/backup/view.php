<?php declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\domain\backup\DomainPayload;
use jaco\core\http\Response;

use function jaco\domain\backup\find_backup;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(string $file): Response
{
    $payload = find_backup(
        file: $file,
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'file' => $payload->data['file'],
            'name' => $payload->data['name'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
            'backedUp' => $payload->data['backedUp'],
            'content' => $payload->data['content'],
            'type' => $payload->data['type'],
        ]);
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

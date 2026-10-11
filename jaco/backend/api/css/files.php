<?php declare(strict_types=1);

namespace jaco\api\css\files;

use jaco\core\http\Response;
use jaco\domain\css\DomainPayload;
use jaco\domain\css\Stylesheet;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\css\find_css_files;

function action(): Response
{
    $payload = find_css_files();

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(
            array_map(fn(Stylesheet $stylesheet): array => [
                'file' => $stylesheet->file,
                'created' => $stylesheet->created,
            ], $payload->data),
        );
    }

    return api_errors($payload);
}

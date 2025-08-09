<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\domain\template\DomainPayload;
use jaco\domain\template\Template;
use jaco\lib\http\Response;
use function jaco\domain\template\find_page_templates;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(): Response
{
    $payload = find_page_templates();

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(
            array_map(fn(Template $item): array => [
                'file' => $item->file,
                'title' => $item->title,
                'created' => $item->created,
            ], $payload->data)
        );
    }

    return api_errors($payload);
}

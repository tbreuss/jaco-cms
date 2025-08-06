<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\domain\template;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(): array
{
    $payload = template\page_templates([]);

    return responder($payload);
}

function responder(template\TemplatePayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok(
            array_map(fn(array $item): array => [
                'file' => $item['file'],
                'title' => $item['title'],
                'created' => $item['created'],
            ], $payload->data)
        );
    }

    return responder\api_errors($payload);
}

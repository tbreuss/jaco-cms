<?php declare(strict_types=1);

namespace jaco\api\template\content;

use jaco\core\http\Response;
use jaco\domain\template\DomainPayload;
use jaco\domain\template\Template;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\find_content_templates;

function action(): Response
{
    $payload = find_content_templates();

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(
            array_map(fn(Template $template): array => [
                'file' => $template->file,
                'title' => $template->title,
                'created' => $template->created,
            ], $payload->data),
        );
    }

    return api_errors($payload);
}

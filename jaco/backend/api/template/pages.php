<?php declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\template\DomainPayload;
use jaco\domain\template\Template;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\find_page_templates;

function action(Config $config): Response
{
    $payload = find_page_templates($config);

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

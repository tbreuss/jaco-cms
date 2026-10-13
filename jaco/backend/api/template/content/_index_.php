<?php declare(strict_types=1);

/**
 * Entry point of the content template api. action() runs the domain function,
 * responder() maps its payload to a response, the view/ namespace holds
 * the single template endpoint.
 */

namespace jaco\api\template\content;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;
use jaco\domain\template\Template;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\find_content_templates;

function action(Config $config): Response
{
    $payload = find_content_templates($config);

    return responder($payload);
}

function responder(Payload $payload): Response
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

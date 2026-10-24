<?php declare(strict_types=1);

namespace jaco\api\page\settings;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\page\update_page_settings;

function action(Config $config, Request $request): Response
{

    $payload = update_page_settings(
        $config,
        file: $request->post->strVal('fileName'),
        title: $request->post->strVal('title'),
        description: $request->post->strVal('description'),
        robots: $request->post->strVal('robots'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response($payload->data);
    }

    return api_errors($payload, [
        'file' => 'fileName',
        'title' => 'pageTitle',
        'description' => 'metaDescription',
        'robots' => 'robots',
        'pageTitle' => 'pageTitle',
        'metaDescription' => 'metaDescription',
    ]);
}

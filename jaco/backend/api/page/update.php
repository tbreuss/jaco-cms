<?php declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\page\update_page;

function action(Config $config, Request $request): Response
{

    $payload = update_page(
        $config,
        file: $request->post->strVal('fileName'),
        withContent: $request->post->strVal('html'),
        withTitle: $request->post->val('title') !== null ? $request->post->strVal('title') : null,
        withDescription: $request->post->val('description') !== null ? $request->post->strVal('description') : null,
        withRobots: $request->post->val('robots') !== null ? $request->post->strVal('robots') : null,
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(status: Status::CREATED);
    }

    return api_errors($payload, [
        'file' => 'fileName',
        'withContent' => 'html',
        'withTitle' => 'pageTitle',
        'withDescription' => 'metaDescription',
        'withRobots' => 'robots',
        'pageTitle' => 'pageTitle',
        'metaDescription' => 'metaDescription',
    ]);
}

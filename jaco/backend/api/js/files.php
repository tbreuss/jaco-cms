<?php declare(strict_types=1);

namespace jaco\api\js\files;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;
use jaco\domain\js\Script;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\js\find_js_files;

function action(Config $config): Response
{
    $payload = find_js_files($config);

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(
            array_map(fn(Script $script): array => [
                'file' => $script->file,
                'created' => $script->created,
            ], $payload->data),
        );
    }

    return api_errors($payload);
}

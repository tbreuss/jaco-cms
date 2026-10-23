<?php declare(strict_types=1);

namespace jaco\api\template\site\list;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\find_site_templates;

function action(Config $config): Response
{
    return responder(find_site_templates($config));
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response($payload->data);
    }

    return api_errors($payload);
}

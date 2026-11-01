<?php declare(strict_types=1);

namespace jaco\api\system\languages;

use jaco\core\config\Config;
use jaco\core\http\Response;

use function jaco\core\http\json_response;
use function jaco\domain\system\list_languages;

function action(Config $config): Response
{
    $payload = list_languages($config);

    return json_response($payload->data);
}

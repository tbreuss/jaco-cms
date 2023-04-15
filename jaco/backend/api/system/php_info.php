<?php

declare(strict_types=1);

namespace jaco\api\system\php_info;

use jaco\response;
use jaco\validate;

#[validate\response('/api/diagnostics/php_info/response.json')]
function action(): array
{
    ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();

    preg_match("/<body[^>]*>(.*?)<\/body>/is", $data, $matches);

    if (empty($matches[1])) {
        return response\internal_server_error([]);
    }

    return response\ok(['content' => $matches[1]]);
}

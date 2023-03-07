<?php

declare(strict_types=1);

namespace jaco\api\system\php_info;

use jaco\response;

function action(): array
{
    ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();

    preg_match("/<body[^>]*>(.*?)<\/body>/is", $data, $matches);

    if (empty($matches[1])) {
        return [response\INTERNAL_SERVER_ERROR];
    }

    return [response\OK, ['content' => $matches[1]]];
}

<?php

declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\response;
use jaco\service\page;
use jaco\validate;

/**
 * @phpstan-return array{0:int, 1?:array{file: string, title: string, created:int, content: ?string}}
 */
#[validate\response('/api/page/view/response.json')]
function action(string $file): array
{
    $page = page\find_one($file);

    if ($page === null) {
        return response\not_found([]);
    }

    return response\ok((array)$page);
}

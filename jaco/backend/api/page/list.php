<?php

declare(strict_types=1);

namespace jaco\api\page\list;

use jaco\response;
use jaco\service\page;

/**
 * @phpstan-return array<int,mixed>
 */
function action(string $filter = '', string $sort = ''): array
{
    [$pages, $filter, $sort] = page\find_all($filter, $sort);
    return response\ok([
        'items' => $pages,
        'filter' => $filter,
        'sort' => $sort,
    ]);
}

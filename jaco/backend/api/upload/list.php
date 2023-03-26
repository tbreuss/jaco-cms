<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\response;
use jaco\service\upload;

/**
 * @phpstan-return array<int,mixed>
 */
function action(string $filter = '', string $sort = '-created'): array
{
    [$uploads, $filter, $sort] = upload\find_all($filter, $sort);
    return [response\OK, [
        'items' => $uploads,
        'filter' => $filter,
        'sort' => $sort,
    ]];
}

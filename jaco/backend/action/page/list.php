<?php

declare(strict_types=1);

namespace jaco\action\page\list;

use jaco\response;

use jaco\service\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $filter = '', string $sort = ''): array
{
    [$items, $filter, $sort] = page\find_all($filter, $sort);
    return [response\OK, [
        'items' => $items,
        'filter' => $filter,
        'sort' => $sort,
    ]];
}

<?php

declare(strict_types=1);

namespace jaco\action\backup\list;

use jaco\response;

use jaco\service\backup;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $filter = '', string $sort = '-created'): array
{
    [$items, $filter, $sort] = backup\find_all($filter, $sort);
    return [response\OK, [
        'items' => $items,
        'filter' => $filter,
        'sort' => $sort,
    ]];
}

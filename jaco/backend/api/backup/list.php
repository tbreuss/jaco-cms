<?php

declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\response;
use jaco\service\backup;

/**
 * @phpstan-return array<int,mixed>
 */
function action(string $filter = '', string $sort = '-created'): array
{
    [$backups, $filter, $sort] = backup\find_all($filter, $sort);
    return [response\OK, [
        'items' => $backups,
        'filter' => $filter,
        'sort' => $sort,
    ]];
}

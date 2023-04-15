<?php

declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\response;
use jaco\service\backup;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\response('/api/backup/list/response.json')]
function action(string $filter = '', string $sort = '-created'): array
{
    [$backups, $filter, $sort] = backup\find_all($filter, $sort);
    return response\ok([
        'items' => $backups,
        'filter' => $filter,
        'sort' => $sort,
    ]);
}

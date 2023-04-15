<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\response;
use jaco\service\upload;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\response('/api/upload/list/response.json')]
function action(string $filter = '', string $sort = '-created'): array
{
    [$uploads, $filter, $sort] = upload\find_all($filter, $sort);
    return response\ok([
        'items' => $uploads,
        'filter' => $filter,
        'sort' => $sort,
    ]);
}

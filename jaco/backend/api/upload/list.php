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
    [$items, $filter, $sort] = upload\find_all($filter, $sort);
    return [response\OK, [
        'items' => $items,
        'filter' => $filter,
        'sort' => $sort,
    ]];
}

/**
 * @return array{string, string}
 */
function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), ['created', 'file', 'title'])) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

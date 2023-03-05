<?php

declare(strict_types=1);

namespace jaco\list;

function filter(array $list, string $filter): array
{
    if (\strlen($filter) === 0) {
        return $list;
    }
    return array_filter($list, function($item) use ($filter) {
        $filter = \str_replace('/', '|', $filter);
        $data = (array)$item;
        foreach ($data as $value) {
            if (\stripos((string)$value, $filter) !== false) {
                return true;
            }
        }
        return false;
    });
}

/**
 * @phpstan-param array{string,string} $sort
 */
function sort(array $list, array $sort): array
{
    [$sortField, $sortDir] = $sort;
    usort($list, function ($a, $b) use ($sortDir, $sortField) {
        return $sortDir === 'asc'
            ? strcmp((string)$a->$sortField, (string)$b->$sortField)
            : strcmp((string)$b->$sortField, (string)$a->$sortField);
    });
    return $list;
}

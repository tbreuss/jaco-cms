<?php declare(strict_types=1);

namespace jaco\core\list;

function filter(array $list, string $filter): array
{
    if (\strlen($filter) === 0) {
        return $list;
    }
    return array_filter($list, function ($item) use ($filter) {
        $filter = \str_replace('/', '|', $filter);
        $data = (array) $item;
        foreach ($data as $value) {
            if (\stripos((string) $value, $filter) !== false) {
                return true;
            }
        }
        return false;
    });
}

function sort(array $list, array $sort): array
{
    [$sortField, $sortDir] = $sort;
    usort($list, function ($a, $b) use ($sortDir, $sortField) {
        $isNumeric = is_numeric($a->$sortField) && is_numeric($b->$sortField);
        if ($sortDir === 'asc') {
            if ($isNumeric) {
                return $a->$sortField <=> $b->$sortField;
            }
            return strcmp((string) $a->$sortField, (string) $b->$sortField);
        }
        if ($isNumeric) {
            return $b->$sortField <=> $a->$sortField;
        }
        return strcmp((string) $b->$sortField, (string) $a->$sortField);
    });
    return $list;
}

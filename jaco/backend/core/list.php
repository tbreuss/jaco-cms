<?php declare(strict_types=1);

namespace jaco\core\list;

function filter_list(array $list, string $filter): array
{
    if (\strlen($filter) === 0) {
        return $list;
    }
    // backup names store "/" as "|", page and upload names keep it: match both
    // spellings, so filtering by a directory path finds its files everywhere
    $variants = [$filter, \str_replace('/', '|', $filter)];
    return array_filter($list, function ($item) use ($variants) {
        $data = (array) $item;
        foreach ($data as $value) {
            foreach ($variants as $variant) {
                if (\stripos((string) $value, $variant) !== false) {
                    return true;
                }
            }
        }
        return false;
    });
}

function sort_list(array $list, array $sort): array
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

/**
 * Parses a sort string like "-created" into a field and direction tuple.
 * Unknown fields fall back to the default, a leading dash means descending.
 *
 * @param list<string> $allowed
 * @param array{string, string} $default
 * @return array{string, string}
 */
function parse_sort(string $sort, array $allowed, array $default = ['created', 'desc']): array
{
    if (!in_array(ltrim($sort, '-'), $allowed)) {
        return $default;
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

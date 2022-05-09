<?php

declare(strict_types=1);

namespace jaco\models\backup;

use jaco\filesystem\file;
use jaco\filesystem\html_file;
use jaco\structs\backup;

/**
 * @phpstan-return array{list:backup[],filter:string,sort:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $files = html_file\find(JACO_BACKUPS_DIR);
    $list = create_list(JACO_BACKUPS_DIR, $files);
    $list = filter_list($list, $filter);
    $sort = sort_field($sort);
    return [
        'list' => sort_list($list, $filter, $sort),
        'filter' => $filter,
        'sort' => $sort
    ];
}

function restore(string $file): bool
{
    return file\restore(JACO_BACKUPS_DIR, $file, JACO_WEB_DIR);
}

function delete(string $file): bool
{
    return file\delete(JACO_BACKUPS_DIR, $file);
}

/**
 * @param \jaco\structs\file[] $files
 * @return backup[]
 */
function create_list(string $directory, array $files): array
{
    $list = [];
    foreach ($files as $file) {
        $list[] = create_one($directory, $file->file);
    }
    return $list;
}

function create_one(string $dir, string $file): backup
{
    $dateTime = \DateTime::createFromFormat('Ymd_His', substr($file, 0, 15));
    return new backup(
        $file,
        str_replace('|', DIRECTORY_SEPARATOR, substr($file, 21)),
        html_file\page_title($dir, $file),
        $dateTime->getTimestamp()
    );
}

/**
 * @phpstan-return array{string,string}
 */
function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), ['created', 'name', 'title'])) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

/**
 * @param backup[] $list
 * @phpstan-param array{string,string} $sort
 * @return backup[]
 */
function sort_list(array $list, string $filter, array $sort): array
{
    [$sortField, $sortDir] = $sort;
    usort($list, function ($a, $b) use ($sortDir, $sortField) {
        return $sortDir === 'asc'
            ? strcmp((string)$a->$sortField, (string)$b->$sortField)
            : strcmp((string)$b->$sortField, (string)$a->$sortField);
    });
    return $list;
}

/**
 * @param backup[] $list
 * @return backup[]
 */
function filter_list(array $list, string $filter): array
{
    if (strlen($filter) === 0) {
        return $list;
    }
    return array_filter($list, function($item) use ($filter) {
        $filter = str_replace('/', '|', $filter);
        return stripos($item->file, $filter) !== false || stripos($item->name, $filter) !== false || stripos($item->title, $filter) !== false;
    });
}

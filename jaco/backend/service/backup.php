<?php

declare(strict_types=1);

namespace jaco\service\backup;

use jaco\filesystem;
use jaco\filesystem\file;
use jaco\filesystem\html_file;
use jaco\list;
use jaco\struct\backup;

use const jaco\PAGE_EXTENSIONS;
use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;
use const jaco\UPLOAD_EXTENSIONS;

/**
 * @phpstan-return array{0:backup[],1:string,2:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $allowed = array_merge(UPLOAD_EXTENSIONS, PAGE_EXTENSIONS);
    $files = filesystem\find_files(BACKUP_DIR, $allowed);
    $list = create_list(BACKUP_DIR, $files, PAGE_EXTENSIONS);
    $list = list\filter($list, $filter);
    $sort = sort_field($sort);
    return [list\sort($list, $sort), $filter, $sort];
}

function find_one(string $file): ?backup
{
    $content = filesystem\read_file(BACKUP_DIR, $file);

    if ($content) {
        $extension = \pathinfo($file, PATHINFO_EXTENSION);
        $type = in_array($extension, PAGE_EXTENSIONS) ? 'page' : 'upload';
        return new backup(
            $file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file, 21)),
            html_file\page_title(BACKUP_DIR, $file),
            filesystem\restore_timestamp_from_file_name($file),
            filesystem\file_mtime($file, BACKUP_DIR),
            $type,
            $type === 'page' ? $content : base64_encode($content)
        );
    }

    return null;
}

function restore(string $file): bool
{
    return filesystem\restore_file(BACKUP_DIR, $file, WEB_DIR);
}

function delete(string $file): bool
{
    return file\delete(BACKUP_DIR, $file);
}

/**
 * @param filesystem\file[] $files
 * @param string[] $pageExtensions
 * @return backup
 */
function create_list(string $dir, array $files, array $pageExtensions): array
{
    $list = [];
    foreach ($files as $file) {
        $list[] = new backup(
            $file->file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file->file, 21)),
            html_file\page_title($dir, $file->file),
            filesystem\restore_timestamp_from_file_name($file->file),
            $file->modified,
            in_array($file->extension, $pageExtensions) ? 'page' : 'upload'
        );
    }
    return $list;
}

/**
 * @phpstan-return array{string,string}
 */
function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), ['backedUp', 'created', 'name', 'title', 'type'])) {
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

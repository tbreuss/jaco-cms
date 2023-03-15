<?php

declare(strict_types=1);

namespace jaco\service\upload;

use jaco\filesystem;
use jaco\list;
use jaco\struct\file;
use jaco\struct\upload;

use const jaco\APP_DIR;
use const jaco\WEB_DIR;
use const jaco\UPLOAD_EXTENSIONS;
use const jaco\BACKUP_DIR;

/**
 * @phpstan-return array{0:upload[],1:string,2:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $files = filesystem\find_files_rec(WEB_DIR, UPLOAD_EXTENSIONS, [APP_DIR]);
    $sort = sort_field($sort);
    $uploads = create_list($files);
    $uploads = list\filter($uploads, $filter);
    $uploads = list\sort($uploads, $sort);
    return [$uploads, $filter, $sort];
}

function delete(string $file): bool
{
    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);
    if (!$status) {
        return false;
    }
    return filesystem\delete_file(WEB_DIR, $file);
}

/**
 * @param file[] $files
 * @return upload[]
 */
function create_list(array $files): array
{
    $list = [];
    foreach ($files as $file) {
        $list[] = new upload(
            $file->file,
            $file->extension,
            $file->size,
            $file->modified,
            null
        );
    }
    return $list;
}

/**
 * @return array{string, string}
 */
function sort_field(string $sort): array
{
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

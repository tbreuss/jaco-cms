<?php

declare(strict_types=1);

namespace jaco\service\backup;

use jaco\filesystem;
use jaco\html;
use jaco\list;
use jaco\struct\backup;
use jaco\struct\file;

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
    $backups = create_list($files, BACKUP_DIR, PAGE_EXTENSIONS);
    $backups = list\filter($backups, $filter);
    $sort = sort_field($sort);
    return [list\sort($backups, $sort), $filter, $sort];
}

function find_one(string $file): ?backup
{
    $content = filesystem\read_file(BACKUP_DIR, $file);

    if (isset($content)) {
        $extension = \pathinfo($file, PATHINFO_EXTENSION);
        $type = in_array($extension, PAGE_EXTENSIONS) ? 'page' : 'upload';
        return new backup(
            $file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file, 21)),
            html\get_title($content),
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
    return filesystem\delete_file(BACKUP_DIR, $file);
}

/**
 * @param file[] $files
 * @param string[] $pageExtensions
 * @return backup[]
 */
function create_list(array $files, string $dir, array $pageExtensions): array
{
    $backups = [];
    foreach ($files as $file) {
        $content = filesystem\read_file($dir, $file->file);
        $backups[] = new backup(
            $file->file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file->file, 21)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file->file),
            $file->modified,
            in_array($file->extension, $pageExtensions) ? 'page' : 'upload'
        );
    }
    return $backups;
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

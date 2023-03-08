<?php

declare(strict_types=1);

namespace jaco\service\page;

use jaco\filesystem;
use jaco\list;
use jaco\html;
use jaco\struct\page;

use const jaco\APP_DIR;
use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

/**
 * @phpstan-return array{0:page[],1:string,2:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $files = filesystem\find_files_rec(WEB_DIR, PAGE_EXTENSIONS, [APP_DIR]);
    $list = create_list(WEB_DIR, $files);
    $list = list\filter($list, $filter);
    $sort = sort_field($sort);
    return [list\sort($list, $sort), $filter, $sort];
}

function find_one(string $file): ?page
{
    $content = filesystem\read_file(WEB_DIR, $file);

    if ($content) {
        return new page(
            $file,
            html\get_title($content),
            filesystem\file_mtime($file, WEB_DIR),
            $content
        );
    }

    return null;
}

function delete(string $file): bool
{
    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);
    if (!$status) {
        return false;
    }
    return filesystem\delete_file(WEB_DIR, $file);
}

function copy(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $contents = filesystem\read_file(WEB_DIR, $srcFile);

    if ($contents === null) {
        return false;
    }

    $contents = html\set_title($contents, $dstPageTitle);

    return filesystem\write_file(WEB_DIR, $dstFile, $contents);
}

function create_from_template(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $contents = filesystem\read_file(TEMPLATE_PAGE_DIR, $srcFile);

    if ($contents === null) {
        return false;
    }

    $contents = html\set_title($contents, $dstPageTitle);

    return filesystem\write_file(WEB_DIR, $dstFile, $contents);
}

function update(string $file, string $content): bool
{
    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);
    if (!$status) {
        return false;
    }

    return filesystem\write_file(WEB_DIR, $file, $content);
}

/**
 * @param filesystem\file[] $files
 * @return page[]
 */
function create_list(string $dir, array $files): array
{
    $html = [];
    foreach ($files as $file) {
        $html[] = create_one($dir, $file->file, $file->modified);
    }
    return $html;
}

function create_one(string $dir, string $file, int $modified, ?string $content = null): page
{
    $filecontent = filesystem\read_file($dir, $file);

    return new page(
        $file,
        html\get_title($filecontent),
        $modified,
        $content
    );
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

<?php

declare(strict_types=1);

namespace jaco\service\page;

use jaco\filesystem\file;
use jaco\filesystem\html_file;
use jaco\struct\page;

use const jaco\PAGE_EXTENSIONS;
use const jaco\BACKUP_DIR;
use const jaco\APP_DIR;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

/**
 * @phpstan-return array{0:page[],1:string,2:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $files = html_file\find_rec(WEB_DIR, [APP_DIR]);
    $list = create_list(WEB_DIR, $files);
    $list = filter_list($list, $filter);
    $sort = sort_field($sort);
    return [sort_list($list, $filter, $sort), $filter, $sort];
}

function find_one(string $file): ?page
{
    $content = file\read(WEB_DIR, $file);

    if ($content) {
        return new page(
            $file,
            html_file\page_title(WEB_DIR, $file),
            file\mtime($file, WEB_DIR),
            $content
        );
    }

    return null;
}


function delete(string $file): bool
{
    $status = file\backup(WEB_DIR, $file, BACKUP_DIR);
    if (!$status) {
        return false;
    }
    return file\delete(WEB_DIR, $file);
}

function exists(string $file): bool
{
    return file\exists(WEB_DIR, $file);
}

function is_writable(string $file): bool
{
    return file\is_writable(WEB_DIR, $file);
}

function is_valid(string $file): bool
{
    $pathinfo = pathinfo($file);

    if (strlen($pathinfo['filename']) === 0) {
        return false;
    }

    // check filename
    preg_match('/[^a-z0-9-_]/', $pathinfo['filename'], $matches);
    if (count($matches) > 0) {
        return false;
    }

    // check extension
    if (!in_array($pathinfo['extension'], PAGE_EXTENSIONS)) {
        return false;
    }

    return true;
}

function copy(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $status = file\copy(WEB_DIR, $srcFile, WEB_DIR, $dstFile);
    if (!$status) {
        throw new \jaco\error\Exception('Could not copy page ' . $srcFile);
    }
    return html_file\update(WEB_DIR, $dstFile, function (string $contents) use ($dstPageTitle): string {
        $contents = preg_replace('/<title>(.*?)<\/title>/', '<title>' . $dstPageTitle . '</title>', $contents);
        if (is_null($contents)) {
            throw new \Exception('Could not edit page title');
        }
        return $contents;
    });
}

function create_from_template(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $status = file\copy(TEMPLATE_PAGE_DIR, $srcFile, WEB_DIR, $dstFile);
    if (!$status) {
        throw new \jaco\error\Exception('Could not create page ' . $srcFile);
    }
    return html_file\update(WEB_DIR, $dstFile, function (string $contents) use ($dstPageTitle): string {
        $contents = preg_replace('/<title>(.*?)<\/title>/', '<title>' . $dstPageTitle . '</title>', $contents);
        if (is_null($contents)) {
            throw new \Exception('Could not edit page title');
        }
        return $contents;
    });
}

function update(string $file, string $content): bool
{
    $callback = function () use ($content): string {
        return $content; // simply return content
    };
    return html_file\update(WEB_DIR, $file, $callback, BACKUP_DIR);
}

/**
 * @param \jaco\struct\file[] $files
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
    return new page(
        $file,
        html_file\page_title($dir, $file),
        $modified,
        $content
    );
}

/**
 * @param page[] $list
 * @phpstan-param array{string,string} $sort
 * @return page[]
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
 * @param page[] $list
 * @return page[]
 */
function filter_list(array $list, string $filter): array
{
    if (strlen($filter) === 0) {
        return $list;
    }
    return array_filter($list, function($item) use ($filter) {
        $filter = str_replace('/', '|', $filter);
        return stripos($item->file, $filter) !== false || stripos($item->title, $filter) !== false;
    });
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

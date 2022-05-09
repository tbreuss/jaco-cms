<?php

declare(strict_types=1);

namespace jaco\models\page;

use jaco\exception;
use jaco\filesystem\file;
use jaco\filesystem\html_file;
use jaco\structs\page;

/**
 * @param string[] $extensions
 * @return string[]
 */
function find_all(string $directory = JACO_WEB_DIR, array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = \scandir($directory, SCANDIR_SORT_ASCENDING);
    return array_filter($files, function (string $file) use ($extensions): bool {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($extension, $extensions);
    });
}

/**
 * @return page[]
 */
function find_all_rec(): array
{
    $files = html_file\find_rec(JACO_WEB_DIR, [JACO_DIR]);
    return create_list(JACO_WEB_DIR, $files);
}

function find_by_name(string $name): string
{
    $all = find_all();
    $key = array_search($name, $all);
    if ($key !== false) {
        return $all[$key];
    }
    throw new exception\Exception('Could not find a page');
}

function get_html(string $page, string $directory = JACO_WEB_DIR): string
{
    $path = rtrim($directory, '/') . '/' . sanitize_page($page);
    $content = file_get_contents($path);
    if ($content === false) {
        throw new \jaco\exception\Exception('Could not load ' . $page);
    }
    return $content;
}

function sanitize_page(string $page): string
{
    $search = ['..', '\\'];
    $replace = '';
    return str_replace($search, $replace, $page);
}

function delete(string $fileName): bool
{
    $status = file\backup(JACO_WEB_DIR, $fileName, JACO_BACKUPS_DIR);
    if (!$status) {
        return false;
    }
    return file\delete(JACO_WEB_DIR, $fileName);
}

function exists(string $fileName): bool
{
    return file\exists(JACO_WEB_DIR, $fileName);
}

function is_writable(string $fileName): bool
{
    return file\is_writable(JACO_WEB_DIR, $fileName);
}

function is_valid(string $fileName): bool
{
    $pathinfo = pathinfo($fileName);

    if (strlen($pathinfo['filename']) === 0) {
        return false;
    }

    // check filename
    preg_match('/[^a-z0-9-_]/', $pathinfo['filename'], $matches);
    if (count($matches) > 0) {
        return false;
    }

    // check extension
    if (!in_array($pathinfo['extension'], JACO_ALLOWED_EXTENSIONS)) {
        return false;
    }

    return true;
}

function copy(string $srcfileName, string $dstFileName, string $dstPageTitle): bool
{
    $status = file\copy(JACO_WEB_DIR, $srcfileName, JACO_WEB_DIR, $dstFileName);
    if (!$status) {
        throw new \jaco\exception\Exception('Could not copy page ' . $srcfileName);
    }
    return html_file\update(JACO_WEB_DIR, $dstFileName, function (string $contents) use ($dstPageTitle): string {
        $contents = preg_replace('/<title>(.*?)<\/title>/', '<title>' . $dstPageTitle . '</title>', $contents);
        if (is_null($contents)) {
            throw new \Exception('Could not edit page title');
        }
        return $contents;
    });
}

function create_from_template(string $srcfileName, string $dstFileName, string $dstPageTitle): bool
{
    $status = file\copy(JACO_TEMPLATES_PAGES_DIR, $srcfileName, JACO_WEB_DIR, $dstFileName);
    if (!$status) {
        throw new \jaco\exception\Exception('Could not create page ' . $srcfileName);
    }
    return html_file\update(JACO_WEB_DIR, $dstFileName, function (string $contents) use ($dstPageTitle): string {
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
    return html_file\update(JACO_WEB_DIR, $file, $callback, JACO_BACKUPS_DIR);
}

/**
 * @param \jaco\structs\file[] $files
 * @return page[]
 */
function create_list(string $directory, array $files): array
{
    $html = [];
    foreach ($files as $file) {
        $html[] = create_one($directory, $file->file, $file->modified);
    }
    return $html;
}

function create_one(string $dir, string $file, int $modified): page
{
    return new page(
        $file,
        html_file\page_title($dir, $file),
        $modified
    );
}

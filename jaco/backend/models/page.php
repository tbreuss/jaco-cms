<?php

declare(strict_types=1);

namespace jaco\models\page;

use jaco\exception;
use jaco\file;

function find_all(string $directory = JACO_WEB_DIR, array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = \scandir($directory, SCANDIR_SORT_ASCENDING);
    return array_filter($files, function (string $file) use ($extensions): bool {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($extension, $extensions);
    });
}

function find_all_rec(): array
{
    $files = file\find_htmlfiles_rec(JACO_WEB_DIR, [JACO_DIR]);
    return $files;
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
    return file\update(JACO_WEB_DIR, $dstFileName, function (string $contents) use ($dstPageTitle): string {
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
    return file\update(JACO_WEB_DIR, $dstFileName, function (string $contents) use ($dstPageTitle): string {
        $contents = preg_replace('/<title>(.*?)<\/title>/', '<title>' . $dstPageTitle . '</title>', $contents);
        if (is_null($contents)) {
            throw new \Exception('Could not edit page title');
        }
        return $contents;
    });
}

<?php

declare(strict_types=1);

namespace jaco\service\page;

use jaco\filesystem\file;
use jaco\filesystem\html_file;
use jaco\struct\page;

/**
 * @return page[]
 */
function find_all(): array
{
    $files = html_file\find_rec(JACO_WEB_DIR, [JACO_DIR]);
    return create_list(JACO_WEB_DIR, $files);
}

function delete(string $file): bool
{
    $status = file\backup(JACO_WEB_DIR, $file, JACO_BACKUPS_DIR);
    if (!$status) {
        return false;
    }
    return file\delete(JACO_WEB_DIR, $file);
}

function exists(string $file): bool
{
    return file\exists(JACO_WEB_DIR, $file);
}

function is_writable(string $file): bool
{
    return file\is_writable(JACO_WEB_DIR, $file);
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
    if (!in_array($pathinfo['extension'], JACO_ALLOWED_EXTENSIONS)) {
        return false;
    }

    return true;
}

function copy(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $status = file\copy(JACO_WEB_DIR, $srcFile, JACO_WEB_DIR, $dstFile);
    if (!$status) {
        throw new \jaco\exception\Exception('Could not copy page ' . $srcFile);
    }
    return html_file\update(JACO_WEB_DIR, $dstFile, function (string $contents) use ($dstPageTitle): string {
        $contents = preg_replace('/<title>(.*?)<\/title>/', '<title>' . $dstPageTitle . '</title>', $contents);
        if (is_null($contents)) {
            throw new \Exception('Could not edit page title');
        }
        return $contents;
    });
}

function create_from_template(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $status = file\copy(JACO_TEMPLATES_PAGES_DIR, $srcFile, JACO_WEB_DIR, $dstFile);
    if (!$status) {
        throw new \jaco\exception\Exception('Could not create page ' . $srcFile);
    }
    return html_file\update(JACO_WEB_DIR, $dstFile, function (string $contents) use ($dstPageTitle): string {
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

function create_one(string $dir, string $file, int $modified): page
{
    return new page(
        $file,
        html_file\page_title($dir, $file),
        $modified
    );
}

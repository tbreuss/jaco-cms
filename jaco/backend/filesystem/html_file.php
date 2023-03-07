<?php

declare(strict_types=1);

namespace jaco\filesystem\html_file;

use jaco\filesystem;
use jaco\filesystem\file;

use const jaco\PAGE_EXTENSIONS;

/**
 * @deprecated
 */
function load(string $dir, string $file): string
{
    if (!filesystem\file_exists($dir, $file)) {
        throw new \Exception('File ' . $file . ' does not exist.');
    }
    $filePath = filesystem\join($dir, $file);
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new \Exception('Could not read file ' . $file);
    }
    return $contents;
}

/**
 * @deprecated
 */
function update(string $dir, string $file, callable $callback, ?string $bakDir = null): bool
{
    $contents = load($dir, $file);
    $edited = call_user_func($callback, $contents);
    if (!is_string($edited)) {
        throw new \Exception('Invalid type from callback');
    }
    if ($bakDir) {
        $status = filesystem\backup_file($dir, $file, $bakDir);
        if (!$status) {
            throw new \Exception('Could not backup file ' . $file);
        }
    }
    $filePath = filesystem\join($dir, $file);
    $status = file_put_contents($filePath, $edited);
    if (!$status) {
        throw new \Exception('Could not save file ' . $file);
    }
    return true;
}

/**
 * @param string[] $extensions
 * @return \jaco\struct\file[]
 * @deprecated
 */
function find(string $dir, array $extensions = PAGE_EXTENSIONS): array
{
    $files = file\scan_dir($dir, $extensions);
    $list = [];
    foreach ($files as $file) {
        $path = filesystem\join($dir, $file);
        $list[] = new \jaco\struct\file($file, filemtime($path));
    }
    return $list;
}

/**
 * @param string[] $exclude_dirs
 * @param string[] $extensions
 * @return \jaco\struct\file[]
 * @deprecated
 */
function find_rec(string $dir, array $exclude_dirs = [], array $extensions = PAGE_EXTENSIONS, int $max_depth = -1): array
{
    $files = file\scan_dir_rec($dir, $extensions, $exclude_dirs, $max_depth);
    $list = [];
    foreach ($files as $file) {
        $path = filesystem\join($dir, $file);
        $list[] = new \jaco\struct\file($file, filemtime($path));
    }
    return $list;
}

/**
 * @deprecated
 */
function page_title(string $dir, string $file): string
{
    $path = filesystem\join($dir, $file);
    $title = '';
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    if ($dom->loadHTMLFile($path)) {
        $list = $dom->getElementsByTagName("title");
        if ($list->length > 0) {
            $title = $list->item(0)->textContent;
        }
    }
    libxml_clear_errors();
    return $title;
}

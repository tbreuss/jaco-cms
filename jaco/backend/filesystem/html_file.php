<?php

declare(strict_types=1);

namespace jaco\filesystem\html_file;

use jaco\filesystem\file;
use jaco\filesystem\shared;

function load(string $dir, string $file): string
{
    if (!file\exists($dir, $file)) {
        throw new \Exception('File ' . $file . ' does not exist.');
    }
    $filePath = shared\join($dir, $file);
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new \Exception('Could not read file ' . $file);
    }
    return $contents;
}

function update(string $dir, string $fileName, callable $callback, ?string $bakDir = null): bool
{
    $contents = load($dir, $fileName);
    $edited = call_user_func($callback, $contents);
    if (!is_string($edited)) {
        throw new \Exception('Invalid type from callback ' . $callback);
    }
    if ($bakDir) {
        $status = file\backup($dir, $fileName, $bakDir);
        if (!$status) {
            throw new \Exception('Could not backup file ' . $fileName);
        }
    }
    $filePath = shared\join($dir, $fileName);
    $status = file_put_contents($filePath, $edited);
    if (!$status) {
        throw new \Exception('Could not save file ' . $fileName);
    }
    return true;
}

function find_htmlfiles(string $directory, array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = file\scandir($directory, $extensions, SCANDIR_SORT_ASCENDING);
    return create_page_list($directory, $files);
}

function find_htmlfiles_rec(string $directory, array $exclude_dirs = [], array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = file\scandir_rec($directory, $extensions, $exclude_dirs);
    return create_page_list($directory, $files);
}

function create_page_list(string $directory, array $files): array
{
    $html = [];
    foreach ($files as $file) {
        $path = shared\join($directory, $file);
        $title = page_title($path);
        $mtime = filemtime($path);
        $html[] = new \jaco\structs\page($file, $title, $mtime);
    }
    return $html;
}

function page_title(string $path): string
{
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

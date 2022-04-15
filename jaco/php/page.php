<?php

namespace jaco\page;

use jaco\exception;

function find_all(string $directory = JACO_WEB_DIR, array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = scandir($directory, SCANDIR_SORT_ASCENDING);
    return array_filter($files, function(string $file) use ($extensions): bool {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($extension, $extensions);
    });
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

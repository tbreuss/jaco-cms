<?php

declare(strict_types=1);

namespace jaco\filesystem\dir;

use jaco\filesystem\shared;

function exists(string $root, string $dir): bool
{
    $path = shared\join($root, $dir);
    return \file_exists($path) && \is_dir($path);
}

function make_dir(string $root, string $directory, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
{
    $path = shared\join($root, $directory);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, false);
}

function make_dir_rec(string $root, string $directory, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
{
    $path = shared\join($root, $directory);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, true);
}
function remove_dir(string $root, string $rel_path): bool
{
    $abs_path = shared\join($root, $rel_path);
    return rmdir($abs_path);
}

function remove_dir_recursive(string $root, string $rel_path): void
{
    $abs_path = shared\join($root, $rel_path);
    _rrmdir($abs_path);
}

function _rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = \scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    _rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

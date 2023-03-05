<?php

declare(strict_types=1);

namespace jaco\filesystem\dir;

use jaco\filesystem\shared;

use const jaco\DIRECTORY_PERMISSIONS;

/**
 * @deprecated
 */
function exists(string $root, string $dir): bool
{
    $path = shared\join($root, $dir);
    return \file_exists($path) && \is_dir($path);
}

/**
 * @deprecated
 */
function is_writable(string $root, string $dir): bool
{
    $path = shared\join($root, $dir);
    return exists($root, $dir) && \is_writable($path);
}

/**
 * @deprecated
 */
function make(string $root, string $dir, int $permissions = DIRECTORY_PERMISSIONS): bool
{
    $path = shared\join($root, $dir);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, false);
}

/**
 * @deprecated
 */
function make_rec(string $root, string $dir, int $permissions = DIRECTORY_PERMISSIONS): bool
{
    $path = shared\join($root, $dir);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, true);
}

/**
 * @deprecated
 */
function remove(string $root, string $dir): bool
{
    $abs_path = shared\join($root, $dir);
    return rmdir($abs_path);
}

/**
 * @deprecated
 */
function remove_rec(string $root, string $dir): void
{
    $abs_path = shared\join($root, $dir);
    _rrmdir($abs_path);
}

/**
 * @deprecated
 */
function _rrmdir(string $dir): void
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

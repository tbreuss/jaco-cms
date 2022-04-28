<?php

namespace jaco\file;

function exists(string $file, string $dir = JACO_WEB_DIR): bool
{
    $path = join($dir, $file);
    return file_exists($path);
}

function is_writable(string $file, string $dir = JACO_WEB_DIR): bool
{
    $path = join($dir, $file);
    return \is_writable($path);
}

function copy(string $srcFile, string $dstFile, string $srcDir = JACO_WEB_DIR, string $dstDir = JACO_WEB_DIR): bool
{
    $srcPath = join($srcDir, $srcFile);
    $dstPath = join($dstDir, $dstFile);
    return \copy($srcPath, $dstPath);
}

function backup(string $file, string $dir = JACO_WEB_DIR, string $bakDir = JACO_BACKUPS_DIR): bool
{
    $srcPath = join($dir, $file);
    $dstPath = join($bakDir, sprintf(
        '%s_%s_%s',
        date('Ymd_His'),
        rand_string(4),
        str_replace('/', '|', $file)
    ));
    return \copy($srcPath, $dstPath);
}

function restore(string $bakFile, string $bakDir = JACO_BACKUPS_DIR, string $dir = JACO_WEB_DIR): bool
{
    $srcPath = join($bakDir, $bakFile);
    $dstPath = join($dir, substr($bakFile, 20));
    if (is_file($dstPath)) {
        // TODO backup first?
        $status = unlink($dstPath);
        if (!$status) {
            // TODO throw error?
            return false;
        }
    }
    return \copy($srcPath, $dstPath);
}

function delete(string $file, string $dir = JACO_WEB_DIR): bool
{
    $path = join($dir, $file);
    return unlink($path);
}

function make_dir(string $directory, string $root = JACO_WEB_DIR, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
{
    $path = join($root, $directory);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, false);
}

function make_dir_rec(string $directory, string $root = JACO_WEB_DIR, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
{
    $path = join($root, $directory);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, true);
}

function join(string $dir, string $file): string
{
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    $file = ltrim($file, DIRECTORY_SEPARATOR);
    return $dir . DIRECTORY_SEPARATOR . $file;
}

function rand_string(int $length = 6): string
{
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

function remove_dir(string $rel_path, string $root = JACO_WEB_DIR): bool
{
    $abs_path = join($root, $rel_path);
    return rmdir($abs_path);
}

function remove_dir_recursive(string $rel_path, string $root = JACO_WEB_DIR): void
{
    $abs_path = join($root, $rel_path);
    _rrmdir($abs_path);
}

function _rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
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

function normalize($dir): string
{
    $normalized = realpath($dir);
    if ($normalized === false) {
        trigger_error('Path ' . $dir . ' is not valid', E_USER_ERROR);
    }
    return $normalized;
}

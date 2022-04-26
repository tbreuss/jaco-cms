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

function make_dir(string $directory, bool $recursive = false, string $root = JACO_WEB_DIR, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
{
    $path = join($root, $directory);
    return mkdir($path, $permissions, $recursive);
}

function join(string $dir, string $file): string
{
    $dir = rtrim($dir, '/');
    $file = ltrim($file, '/');
    return $dir . '/' . $file;
}

function rand_string(int $length = 6): string
{
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

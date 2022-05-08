<?php

declare(strict_types=1);

namespace jaco\filesystem\file;

use jaco\filesystem\dir;
use jaco\filesystem\shared;

function exists(string $dir, string $file): bool
{
    $path = shared\join($dir, $file);
    return \file_exists($path) && \is_file($path);
}

function is_writable(string $dir, string $file): bool
{
    $path = shared\join($dir, $file);
    return exists($dir, $file) && \is_writable($path);
}

function copy(string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = shared\join($srcDir, $srcFile);
    $dstPath = shared\join($dstDir, $dstFile);
    $srcDirname = dirname($srcFile);
    $dstDirname = dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        dir\make_rec($dstDir, $dstDirname);
    }
    return \copy($srcPath, $dstPath);
}

function move(string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = shared\join($srcDir, $srcFile);
    $dstPath = shared\join($dstDir, $dstFile);
    $srcDirname = dirname($srcFile);
    $dstDirname = dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        dir\make_rec($dstDir, $dstDirname);
    }
    return \rename($srcPath, $dstPath);
}

function delete(string $dir, string $file): bool
{
    $path = shared\join($dir, $file);
    return unlink($path);
}

function backup(string $dir, string $file, string $bakDir): bool
{
    $srcPath = shared\join($dir, $file);
    $dstPath = shared\join($bakDir, sprintf(
        '%s_%s_%s',
        date('Ymd_His'),
        shared\rand_string(4),
        str_replace('/', '|', $file)
    ));
    return \copy($srcPath, $dstPath); // triggers an error
}

function restore(string $bakDir, string $bakFile, string $dstDir): bool
{
    $dstFile = str_replace('|', '/', substr($bakFile, 21));
    if (exists($dstDir, $dstFile)) {
        // TODO backup first?
        $status = delete($dstDir, $dstFile);
        if (!$status) {
            // TODO throw error?
            return false;
        }
    }
    return move($bakDir, $bakFile, $dstDir, $dstFile);
}

function save(string $dir, string $file, string $contents): bool
{
    return false;
}

function validate_filename(string $dir, string $fileName, bool $isBackup = false): bool
{
    $abs_path = shared\join($dir, $fileName);

    $pathinfo = pathinfo($abs_path);

    if (strlen($pathinfo['filename']) === 0) {
        return false;
    }

    // check filename
    $pattern = $isBackup ? '/[^a-z0-9-_|]/' : '/[^a-z0-9-_]/';
    preg_match($pattern, $pathinfo['filename'], $matches);
    if (count($matches) > 0) {
        return false;
    }

    return true;
}

function validate_extension(string $dir, string $fileName, array $allowed = JACO_ALLOWED_EXTENSIONS): bool
{
    $abs_path = shared\join($dir, $fileName);

    $extension = pathinfo($abs_path, PATHINFO_EXTENSION);

    if (!in_array($extension, $allowed)) {
        return false;
    }

    return true;
}

function scandir(string $directory, array $extensions, int $sorting_order): array
{
    $files = \scandir($directory, $sorting_order);
    $filtered = array_filter($files, function (string $file) use ($extensions): bool {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($extension, $extensions);
    });
    return array_values($filtered);
}

function scandir_rec(string $directory, array $extensions, array $excludedDirectories): array
{
    $rii = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory)
    );

    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        if (!in_array($file->getExtension(), $extensions)) {
            continue;
        }
        $pathname = $file->getPathname();
        foreach ($excludedDirectories as $excludeDir) {
            if (strpos($pathname, $excludeDir) !== false) {
                continue 2;
            }
        }
        if (strpos($pathname, $directory) === 0) {
            $relfile = substr($pathname, strlen($directory));
            $files[] = ltrim($relfile, '\\/');
        }
    }

    return $files;
}

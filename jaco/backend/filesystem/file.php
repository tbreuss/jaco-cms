<?php

declare(strict_types=1);

namespace jaco\filesystem\file;

use jaco\filesystem\dir;
use jaco\filesystem\shared;

use const jaco\ALLOWED_EXTENSIONS;

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

function read(string $dir, string $file): ?string
{
    if (exists($dir, $file)) {
        $content = file_get_contents(shared\join($dir, $file));
        if (is_string($content)) {
            return $content;
        }
    }
    return null;
}

/**
 * Writes data to a file rooted at dir.
 * @param string $dir
 * @param string $file
 * @param string $data
 * @return bool
 */
function write(string $dir, string $file, string $data): bool
{
    return false;
}

function validate_filename(string $dir, string $file, bool $isBackup = false): bool
{
    $abs_path = shared\join($dir, $file);

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

/**
 * @param string[] $allowed
 */
function validate_extension(string $dir, string $file, array $allowed = ALLOWED_EXTENSIONS): bool
{
    $abs_path = shared\join($dir, $file);

    $extension = pathinfo($abs_path, PATHINFO_EXTENSION);

    if (!in_array($extension, $allowed)) {
        return false;
    }

    return true;
}

/**
 * @param string[] $extensions
 * @return string[]
 */
function scan_dir(string $dir, array $extensions): array
{
    return scan_dir_rec($dir, $extensions, [], 0);
}

/**
 * @param string[] $extensions
 * @param string[] $excludedDirectories
 * @return string[]
 */
function scan_dir_rec(string $dir, array $extensions, array $excludedDirectories = [], int $maxDepth = -1): array
{
    $rii = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir)
    );
    $rii->setMaxDepth($maxDepth);

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
            if (str_contains($pathname, rtrim($excludeDir, '/') . '/')) {
                continue 2;
            }
        }
        if (str_starts_with($pathname, $dir)) {
            $relfile = substr($pathname, strlen($dir));
            $files[] = ltrim($relfile, '\\/');
        }
    }

    return $files;
}

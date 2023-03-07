<?php

declare(strict_types=1);

namespace jaco\filesystem\file;

use jaco\filesystem\dir;
use jaco\filesystem\shared;

use const jaco\PAGE_EXTENSIONS;

/**
 * @deprecated
 */
function exists(string $dir, string $file): bool
{
    $path = join($dir, $file);
    return \file_exists($path) && \is_file($path);
}

/**
 * @deprecated
 */
function is_writable(string $dir, string $file): bool
{
    $path = join($dir, $file);
    return exists($dir, $file) && \is_writable($path);
}

/**
 * @deprecated
 */
function copy(string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = join($srcDir, $srcFile);
    $dstPath = join($dstDir, $dstFile);
    $srcDirname = dirname($srcFile);
    $dstDirname = dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        dir\make_rec($dstDir, $dstDirname);
    }
    return \copy($srcPath, $dstPath);
}

/**
 * @deprecated
 */
function move(string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = join($srcDir, $srcFile);
    $dstPath = join($dstDir, $dstFile);
    $srcDirname = dirname($srcFile);
    $dstDirname = dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        dir\make_rec($dstDir, $dstDirname);
    }
    return \rename($srcPath, $dstPath);
}

/**
 * @deprecated
 */
function delete(string $dir, string $file): bool
{
    $path = join($dir, $file);
    return unlink($path);
}

/**
 * @deprecated
 */
function backup(string $dir, string $file, string $bakDir): bool
{
    $srcPath = join($dir, $file);
    $dstPath = join($bakDir, sprintf(
        '%s_%s_%s',
        date('Ymd_His'),
        rand_string(4),
        str_replace('/', '|', $file)
    ));
    return \copy($srcPath, $dstPath); // triggers an error
}

/**
 * @deprecated
 */
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

/**
 * @deprecated
 */
function read(string $dir, string $file): ?string
{
    if (exists($dir, $file)) {
        $content = file_get_contents(join($dir, $file));
        if (is_string($content)) {
            return $content;
        }
    }
    return null;
}

/**
 * @deprecated
 */
function mtime(string $file, string $dir): int
{
    $time = filemtime(join($dir, $file));
    return is_int($time) ? $time : 0;
}

/**
 * Writes data to a file rooted at dir.
 * @param string $dir
 * @param string $file
 * @param string $data
 * @return bool
 * @deprecated
 */
function write(string $dir, string $file, string $data): bool
{
    // TODO
    return false;
}

/**
 * @deprecated
 */
function validate_filename(string $dir, string $file, bool $isBackup = false): bool
{
    $abs_path = join($dir, $file);

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
 * @deprecated
 */
function validate_extension(string $dir, string $file, array $allowed = PAGE_EXTENSIONS): bool
{
    $abs_path = join($dir, $file);

    $extension = pathinfo($abs_path, PATHINFO_EXTENSION);

    if (!in_array($extension, $allowed)) {
        return false;
    }

    return true;
}

/**
 * @param string[] $extensions
 * @return string[]
 * @deprecated
 */
function scan_dir(string $dir, array $extensions): array
{
    return scan_dir_rec($dir, $extensions, [], 0);
}

/**
 * @param string[] $extensions
 * @param string[] $excludedDirectories
 * @return string[]
 * @deprecated
 */
function scan_dir_rec(string $dir, array $extensions, array $excludedDirectories = [], int $maxDepth = -1): array
{
    $excludedFiles = ['.git', '.idea', '.DS_Store', '.vscode', '.gitignore'];

    $onFileAcceptHandler = function (\SplFileInfo $file) use ($extensions, $excludedDirectories, $excludedFiles) {
        if ($file->isDir()) {
            foreach ($excludedDirectories as $excludeDir) {
                if (str_contains($file->getPathname(), rtrim($excludeDir, '/') . '/')) {
                    return false;
                }
            }
        }
        if (in_array($file->getFilename(), $excludedFiles)) {
            return false;
        }
        if ($file->isFile() && !in_array($file->getExtension(), $extensions)) {
            return false;
        }
        return true;
    };

    $rii = new \RecursiveIteratorIterator(
        new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            $onFileAcceptHandler
        )
    );

    $rii->setMaxDepth($maxDepth);

    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $pathname = $file->getPathname();
        $relfile = substr($pathname, strlen($dir));
        $files[] = ltrim($relfile, '\\/');
    }

    return $files;
}

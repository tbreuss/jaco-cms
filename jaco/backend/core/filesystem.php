<?php declare(strict_types=1);

namespace jaco\core\filesystem;

use jaco\core\config\Config;
use jaco\core\strings;

const BACKUP_NAME_PREFIX_LENGTH = 21; // {Ymd_His}_{random 4}_ prefix of backup file names

final class File
{
    public function __construct(
        public string $file,
        public string $extension,
        public int $modified,
        public int $size,
        public ?string $content = null,
    ) {}
}

function dir_exists(string $root, string $dir = ''): bool
{
    $path = combine($root, $dir);
    return \file_exists($path) && \is_dir($path);
}

function dir_is_writable(string $root, string $dir): bool
{
    $path = combine($root, $dir);
    return dir_exists($root, $dir) && \is_writable($path);
}

function make_dir(Config $config, string $root, string $dir, ?int $permissions = null): bool
{
    $permissions = $permissions ?? $config->directoryPermissions;
    $path = combine($root, $dir);
    if (\is_dir($path)) {
        return true;
    }
    return \mkdir($path, $permissions);
}

function make_dir_rec(Config $config, string $root, string $dir, ?int $permissions = null): bool
{
    $permissions = $permissions ?? $config->directoryPermissions;
    $path = combine($root, $dir);
    if (\is_dir($path)) {
        return true;
    }
    return \mkdir($path, $permissions, true);
}

function remove_dir(string $root, string $dir): bool
{
    $abs_path = combine($root, $dir);
    return \rmdir($abs_path);
}

function remove_dir_rec(string $root, string $dir): void
{
    $abs_path = combine($root, $dir);
    _rrmdir($abs_path);
}

function combine(string $dir, string $file): string
{
    $dir = \rtrim($dir, DIRECTORY_SEPARATOR);
    $file = \ltrim($file, DIRECTORY_SEPARATOR);
    return $dir . DIRECTORY_SEPARATOR . $file;
}

function file_exists(string $dir, string $file): bool
{
    $path = combine($dir, $file);
    return \file_exists($path) && \is_file($path);
}

function file_is_writable(string $dir, string $file): bool
{
    $path = combine($dir, $file);
    return file_exists($dir, $file) && \is_writable($path);
}

function copy_file(Config $config, string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = combine($srcDir, $srcFile);
    $dstPath = combine($dstDir, $dstFile);
    $srcDirname = \dirname($srcFile);
    $dstDirname = \dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        make_dir_rec($config, $dstDir, $dstDirname);
    }
    return \copy($srcPath, $dstPath);
}

function move_file(Config $config, string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = combine($srcDir, $srcFile);
    $dstPath = combine($dstDir, $dstFile);
    $srcDirname = \dirname($srcFile);
    $dstDirname = \dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        make_dir_rec($config, $dstDir, $dstDirname);
    }
    return \rename($srcPath, $dstPath);
}

function touch_file(string $file, string $inDir, int $timestamp = 0): bool
{
    $path = combine($inDir, $file);
    return \touch($path, $timestamp);
}

function delete_file(string $dir, string $file): bool
{
    $path = combine($dir, $file);
    return \unlink($path);
}

function backup_file(string $dir, string $file, string $bakDir): bool
{
    $modTime = file_mtime($file, $dir);
    $srcPath = combine($dir, $file);
    $dstPath = combine(
        $bakDir,
        \sprintf(
            '%s_%s_%s',
            \date('Ymd_His', $modTime),
            strings\random(4),
            \str_replace('/', '|', $file),
        ),
    );
    return \copy($srcPath, $dstPath); // triggers an error
}

function restore_file(Config $config, string $bakDir, string $bakFile, string $dstDir): bool
{
    $dstFile = \str_replace('|', '/', \substr($bakFile, BACKUP_NAME_PREFIX_LENGTH));

    // rename replaces an existing target file atomically,
    // so the current file is kept if the backup cannot be moved
    if (!move_file($config, $bakDir, $bakFile, $dstDir, $dstFile)) {
        return false;
    }

    $timestamp = restore_timestamp_from_file_name($bakFile);

    return touch_file($dstFile, $dstDir, $timestamp);
}

function restore_timestamp_from_file_name(string $bakFile): int
{
    $bakFile = \str_replace('_', '', \substr($bakFile, 0, 15));
    return \strtotime($bakFile);
}

function read_file(string $dir, string $file): ?string
{
    if (file_exists($dir, $file)) {
        $content = \file_get_contents(combine($dir, $file));
        if (\is_string($content)) {
            return $content;
        }
    }
    return null;
}

function file_mtime(string $file, string $dir): int
{
    $time = \filemtime(combine($dir, $file));
    return \is_int($time) ? $time : 0;
}

function write_file(Config $config, string $dir, string $file, string $data): bool
{
    $filePath = combine($dir, $file);
    $fileDir = \dirname($file);
    if (!dir_exists($dir, $fileDir)) {
        $status = make_dir_rec($config, $dir, $fileDir);
        if ($status === false) {
            return false;
        }
    }
    $status = file_put_contents($filePath, $data);
    return !($status === false);
}

function write_file_without_overwrite(Config $config, string $dir, string $file, string $data): array
{
    $newFile = file_name_to_write($dir, $file);

    if (write_file($config, $dir, $newFile, $data)) {
        return [true, $newFile];
    }

    return [false];
}

function file_name_to_write(string $dir, string $file): string
{
    if (file_exists($dir, $file)) {
        $info = \pathinfo($file);
        $extension = $info['extension'] ?? '';
        $prefix = $info['dirname'] === '.' ? '' : $info['dirname'] . DIRECTORY_SEPARATOR;
        $i = 1;

        do {
            $file = $prefix . $info['filename'] . '-' . $i;

            if ($extension !== '') {
                $file = $file . '.' . $extension;
            }

            $i++;
        } while (file_exists($dir, $file));
    }

    return $file;
}

function validate_filename(string $file, bool $isBackup = false): bool
{
    if (\strlen($file) === 0) {
        return false;
    }

    $filename = \pathinfo($file, \PATHINFO_FILENAME);

    if (\strlen($filename) === 0) {
        return false;
    }

    // check filename
    $pattern = $isBackup ? '/[^A-Za-z0-9-_|]/' : '/[^A-Za-z0-9-_]/';
    \preg_match($pattern, $filename, $matches);
    if (\count($matches) > 0) {
        return false;
    }

    return true;
}

/**
 * @param string[] $allowed
 */
function validate_extension(string $file, array $allowed): bool
{
    $extension = \pathinfo($file, \PATHINFO_EXTENSION);

    if (!\in_array($extension, $allowed)) {
        return false;
    }

    return true;
}

/**
 * @param string[] $allowedExtensions
 * @return File[]
 */
function find_files(string $inDir, array $allowedExtensions, bool $withContent = false): array
{
    return find_files_rec($inDir, $allowedExtensions, [], 0, $withContent);
}

/**
 * @param string[] $extensions
 * @param string[] $excludedDirectories
 * @return File[]
 */
function find_files_rec(
    string $dir,
    array $extensions,
    array $excludedDirectories = [],
    int $maxDepth = -1,
    bool $withContent = false,
): array {
    $excludedFiles = ['.git', '.idea', '.DS_Store', '.vscode', '.gitignore'];

    $onFileAcceptHandler = function (\SplFileInfo $file) use ($extensions, $excludedDirectories, $excludedFiles) {
        foreach ($excludedDirectories as $excludeDir) {
            if (\str_contains($file->getPathname(), \rtrim($excludeDir, '/') . '/')) {
                return false;
            }
        }
        if (\in_array($file->getFilename(), $excludedFiles)) {
            return false;
        }
        if ($file->isFile() && !\in_array($file->getExtension(), $extensions)) {
            return false;
        }
        return true;
    };

    $rii = new \RecursiveIteratorIterator(
        new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            $onFileAcceptHandler,
        ),
    );

    $rii->setMaxDepth($maxDepth);

    $files = [];
    foreach ($rii as $file) {
        /** @var \SplFileInfo $file */
        if ($file->isDir()) {
            continue;
        }
        $pathname = $file->getPathname();
        $relfile = \substr($pathname, \strlen($dir));
        $files[] = new File(
            \ltrim($relfile, '\\/'),
            $file->getExtension(),
            $file->getMTime(),
            $file->getSize(),
            $withContent ? read_file($dir, $relfile) : null,
        );
    }

    return $files;
}

function _rrmdir(string $dir): void
{
    if (\is_dir($dir)) {
        $objects = \scandir($dir);
        if (!\is_array($objects)) {
            return;
        }
        foreach ($objects as $object) {
            if (($object != ".") && ($object != "..")) {
                if (\is_dir($dir . DIRECTORY_SEPARATOR . $object) && !\is_link($dir . "/" . $object)) {
                    _rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    \unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        \rmdir($dir);
    }
}

function filter_filename(string $file): string
{
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), function ($value) {
        return strlen($value) > 0;
    });

    $absolutes = [];
    foreach ($parts as $part) {
        $part = trim($part, '.');
        if ($part === '') {
            continue;
        }
        $absolutes[] = $part;
    }

    return implode(DIRECTORY_SEPARATOR, $absolutes);
}

function file_in_dir(string $file, string $dir): bool
{
    $parts = explode(DIRECTORY_SEPARATOR, $file);
    return $parts[0] === $dir;
}

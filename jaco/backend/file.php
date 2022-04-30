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

function load(string $srcFile): string
{
    if (!exists($srcFile)) {
        throw new \Exception('File ' . $srcFile . ' does not exist.');
    }
    $filePath = join(JACO_WEB_DIR, $srcFile);
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new \Exception('Could not read file ' . $srcFile);
    }
    return $contents;
}

function save(string $fileName, string $contents): bool
{
    return false;
}

function copy(string $srcFile, string $dstFile, string $srcDir = JACO_WEB_DIR, string $dstDir = JACO_WEB_DIR): bool
{
    $srcPath = join($srcDir, $srcFile);
    $dstPath = join($dstDir, $dstFile);
    return \copy($srcPath, $dstPath);
}

function update(string $fileName, callable $callback, bool $backup = false): bool
{
    $contents = load($fileName);
    $edited = call_user_func($callback, $contents);
    if (!is_string($edited)) {
        throw new \Exception('Invalid type from callback ' . $callback);
    }
    if ($backup) {
        $status = backup($fileName);
        if (!$status) {
            throw new \Exception('Could not backup file ' . $fileName);
        }
    }
    $filePath = join(JACO_WEB_DIR, $fileName);
    $status = file_put_contents($filePath, $edited);
    if (!$status) {
        throw new \Exception('Could not save file ' . $fileName);
    }
    return true;
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

function validate_filename(string $fileName): bool
{
    $abs_path = join(JACO_WEB_DIR, $fileName);

    $pathinfo = pathinfo($abs_path);

    if (strlen($pathinfo['filename']) === 0) {
        return false;
    }

    // check filename
    preg_match('/[^a-z0-9-_]/', $pathinfo['filename'], $matches);
    if (count($matches) > 0) {
        return false;
    }

    return true;
}

function validate_extension(string $fileName, array $allowed = JACO_ALLOWED_EXTENSIONS): bool
{
    $abs_path = join(JACO_WEB_DIR, $fileName);

    $extension = pathinfo($abs_path, PATHINFO_EXTENSION);

    if (!in_array($extension, $allowed)) {
        return false;
    }

    return true;
}

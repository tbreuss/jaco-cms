<?php

declare(strict_types=1);

namespace jaco\file;

function exists(string $dir, string $file): bool
{
    $path = join($dir, $file);
    return file_exists($path);
}

function is_writable(string $dir, string $file): bool
{
    $path = join($dir, $file);
    return \is_writable($path);
}

function load(string $dir, string $file): string
{
    if (!exists($dir, $file)) {
        throw new \Exception('File ' . $file . ' does not exist.');
    }
    $filePath = join($dir, $file);
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new \Exception('Could not read file ' . $file);
    }
    return $contents;
}

function save(string $dir, string $file, string $contents): bool
{
    return false;
}

function copy(string $srcDir, string $srcFile, string $dstDir, string $dstFile): bool
{
    $srcPath = join($srcDir, $srcFile);
    $dstPath = join($dstDir, $dstFile);
    $srcDirname = dirname($srcFile);
    $dstDirname = dirname($dstFile);
    if ($srcDirname !== $dstDirname) {
        make_dir_rec($dstDir, $dstDirname);
    }
    return \copy($srcPath, $dstPath);
}

function update(string $dir, string $fileName, callable $callback, ?string $bakDir = null): bool
{
    $contents = load($dir, $fileName);
    $edited = call_user_func($callback, $contents);
    if (!is_string($edited)) {
        throw new \Exception('Invalid type from callback ' . $callback);
    }
    if ($bakDir) {
        $status = backup($dir, $fileName, $bakDir);
        if (!$status) {
            throw new \Exception('Could not backup file ' . $fileName);
        }
    }
    $filePath = join($dir, $fileName);
    $status = file_put_contents($filePath, $edited);
    if (!$status) {
        throw new \Exception('Could not save file ' . $fileName);
    }
    return true;
}

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

function restore(string $bakDir, string $bakFile, string $dir): bool
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

function delete(string $dir, string $file): bool
{
    $path = join($dir, $file);
    return unlink($path);
}

function make_dir(string $root, string $directory, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
{
    $path = join($root, $directory);
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, $permissions, false);
}

function make_dir_rec(string $root, string $directory, int $permissions = JACO_PERMISSIONS_DIRECTORIES): bool
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

function remove_dir(string $root, string $rel_path): bool
{
    $abs_path = join($root, $rel_path);
    return rmdir($abs_path);
}

function remove_dir_recursive(string $root, string $rel_path): void
{
    $abs_path = join($root, $rel_path);
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

function normalize($dir): string
{
    $normalized = realpath($dir);
    if ($normalized === false) {
        trigger_error('Path ' . $dir . ' is not valid', E_USER_ERROR);
    }
    return $normalized;
}

function validate_filename(string $dir, string $fileName): bool
{
    $abs_path = join($dir, $fileName);

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

function validate_extension(string $dir, string $fileName, array $allowed = JACO_ALLOWED_EXTENSIONS): bool
{
    $abs_path = join($dir, $fileName);

    $extension = pathinfo($abs_path, PATHINFO_EXTENSION);

    if (!in_array($extension, $allowed)) {
        return false;
    }

    return true;
}

function find_htmlfiles(string $directory, array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = scandir($directory, $extensions, SCANDIR_SORT_ASCENDING);
    return create_page_list($directory, $files);
}

function find_htmlfiles_rec(string $directory, array $exclude_dirs = [], array $extensions = JACO_ALLOWED_EXTENSIONS): array
{
    $files = scandir_rec($directory, $extensions, $exclude_dirs);
    return create_page_list($directory, $files);
}

function create_page_list(string $directory, array $files): array
{
    $html = [];
    foreach ($files as $file) {
        $path = join($directory, $file);
        $title = page_title($path);
        $mtime = filemtime($path);
        $html[] = new \jaco\structs\page($file, $title, $mtime);
    }
    return $html;
}

function page_title(string $path): string
{
    $title = '';
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    if ($dom->loadHTMLFile($path)) {
        $list = $dom->getElementsByTagName("title");
        if ($list->length > 0) {
            $title = $list->item(0)->textContent;
        }
    }
    libxml_clear_errors();
    return $title;
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

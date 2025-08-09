<?php declare(strict_types=1);

function find_php_files(string $dir): array
{
    $files = [];
    foreach (scandir($dir) as $dirOrFile) {
        if (in_array($dirOrFile, ['.', '..'])) {
            continue;
        }
        $path = realpath($dir . DIRECTORY_SEPARATOR . $dirOrFile);
        if (is_dir($path)) {
            $files = array_merge($files, find_php_files($path));
        } elseif (str_ends_with($path, '.php')) {
            $files[] = $path;
        }
    }
    return $files;
}

$files = find_php_files(__DIR__ . '/backend/');

foreach ($files as $file) {
    require_once $file;
}

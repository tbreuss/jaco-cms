<?php

declare(strict_types=1);

function dir_recursive($dir, &$results = array()) {
    foreach (scandir($dir) as $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            dir_recursive($path, $results);
        }
    }
    return $results;
}

$files = dir_recursive(__DIR__ . '/backend/');

foreach ($files as $file) {
    require_once $file;
}

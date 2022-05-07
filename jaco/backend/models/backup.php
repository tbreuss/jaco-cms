<?php

declare(strict_types=1);

namespace jaco\models\backup;

use jaco\filesystem\html_file;
use jaco\structs\backup;

/**
 * @return \jaco\structs\backup[]
 */
function find_all(): array
{
    $files = html_file\find(JACO_BACKUPS_DIR);
    return create_list(JACO_BACKUPS_DIR, $files);
}

/**
 * @param \jaco\structs\file[] $files
 * @return \jaco\structs\backup[]
 */
function create_list(string $directory, array $files): array
{
    $list = [];
    foreach ($files as $file) {
        $list[] = create_one($directory, $file->file);
    }
    return $list;
}

function create_one(string $dir, string $file): backup
{
    $dateTime = \DateTime::createFromFormat('Ymd_His', substr($file, 0, 15));
    return new backup(
        $file,
        str_replace('|', DIRECTORY_SEPARATOR, substr($file, 21)),
        html_file\page_title($dir, $file),
        $dateTime->getTimestamp()
    );
}

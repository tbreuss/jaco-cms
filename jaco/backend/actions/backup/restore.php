<?php

declare(strict_types=1);

namespace jaco\actions\backup\restore;

use jaco\filter;
use jaco\request;
use jaco\validate;
use jaco\models\backup;

function main(): array
{
    // get raw user input
    $raw = request\post_values();

    // filter data
    $data = filter\all($raw, filters());

    // validate data
    $errors = validate\all($data, validators());
    if (count($errors) > 0) {
        return [422, $errors];
    }

    // delete page
    if (!backup\restore($data['file'])) {
        return [500, [['title' => 'Could not restore backup']]];
    }

    return [200];
}

function filters(): array
{
    return [
        ['file', 'filename']
    ];
}

function validators(): array
{
    return [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => JACO_BACKUPS_DIR],
        ['file', 'htmlfile_name', 'isBackup' => true],
        ['file', 'htmlfile_extension']
    ];
}

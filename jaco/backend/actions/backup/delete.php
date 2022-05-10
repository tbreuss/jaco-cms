<?php

declare(strict_types=1);

namespace jaco\actions\backup\delete;

use jaco\filter;
use jaco\request;
use jaco\validate;
use jaco\models\backup;

/**
 * @phpstan-return array<int,mixed>
 */
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
    if (!backup\delete($data['file'])) {
        return [500, [['title' => 'Could not restore backup']]];
    }

    return [200];
}

/**
 * @phpstan-return array<int,array<int,string>>
 */
function filters(): array
{
    return [
        ['file', 'filename']
    ];
}

/**
 * @phpstan-return array<int,array<int|string,bool|string>>
 */
function validators(): array
{
    return [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => JACO_BACKUPS_DIR],
    ];
}

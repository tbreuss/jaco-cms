<?php

declare(strict_types=1);

namespace jaco\action\backup\delete;

use jaco\data;
use jaco\request;
use jaco\service\backup;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    // get raw user input
    $raw = request\post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());
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
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['file', 'jaco\data\filter\filename']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['file', 'jaco\data\validator\required'],
        ['file', 'jaco\data\validator\file_exists', 'dir' => JACO_BACKUPS_DIR],
    ];
}

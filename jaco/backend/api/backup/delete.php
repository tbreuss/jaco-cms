<?php

declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\data;
use jaco\request;
use jaco\response;
use jaco\service\backup;

use const jaco\BACKUP_DIR;

/**
 * @phpstan-return array<int,mixed>
 */
function action(): array
{
    $raw = request\get_post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());
    if (count($errors) > 0) {
        return [response\UNPROCESSABLE_CONTENT, $errors];
    }

    if (backup\delete($data['file'])) {
        return [response\OK];
    }

    return [response\INTERNAL_SERVER_ERROR, [['title' => 'Could not restore backup']]];
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
        ['file', 'jaco\data\validator\file_exists', 'dir' => BACKUP_DIR],
    ];
}

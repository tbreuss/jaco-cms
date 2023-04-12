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
        return response\unprocessable_entity($errors);
    }

    if (backup\delete($data['file'])) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not restore backup']]);
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['file', 'filename']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => BACKUP_DIR],
    ];
}

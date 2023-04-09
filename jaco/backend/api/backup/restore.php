<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\data;
use jaco\request;
use jaco\response;
use jaco\service\backup;

use const jaco\PAGE_EXTENSIONS;
use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_EXTENSIONS;

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

    if (backup\restore($data['file'])) {
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
        ['file', 'jaco\data\validator\htmlfile_name', 'isBackup' => true],
        ['file', 'jaco\data\validator\file_extension', 'allowed' => array_merge(PAGE_EXTENSIONS, UPLOAD_EXTENSIONS)],
    ];
}

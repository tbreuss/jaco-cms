<?php

declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\data;
use jaco\request;
use jaco\response;
use jaco\service\upload;

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
        return [response\UNPROCESSABLE_CONTENT, $errors];
    }

    if (upload\delete($data['fileName'])) {
        return [response\OK];
    }

    return [response\INTERNAL_SERVER_ERROR, [['title' => 'Could not delete upload']]];
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['fileName', 'jaco\data\filter\filename']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['fileName', 'jaco\data\validator\required'],
        ['fileName', 'jaco\data\validator\file_exists'],
        ['fileName', 'jaco\data\validator\file_writeable'],
        ['fileName', 'jaco\data\validator\htmlfile_name'],
        ['fileName', 'jaco\data\validator\file_extension', 'allowed' => UPLOAD_EXTENSIONS]
    ];
}

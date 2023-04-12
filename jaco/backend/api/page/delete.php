<?php

declare(strict_types=1);

namespace jaco\api\page\delete;

use jaco\data;
use jaco\request;
use jaco\response;
use jaco\service\page;

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

    if (page\delete($data['fileName'])) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not delete page']]);
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['fileName', 'filename']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['fileName', 'required'],
        ['fileName', 'file_exists'],
        ['fileName', 'file_writeable'],
        ['fileName', 'htmlfile_name'],
        ['fileName', 'htmlfile_extension']
    ];
}

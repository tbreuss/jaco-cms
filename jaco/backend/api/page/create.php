<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\data;
use jaco\request;
use jaco\response;
use jaco\service\page;

use const jaco\APP_DIR_NAME;

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

    if (page\create_from_template($data['pageTemplate'], $data['fileName'], $data['pageTitle'])) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not create page']]);
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['pageTemplate', 'filename'],
        ['fileName', 'filename'],
        ['pageTitle', 'html_specialchars'],
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['pageTemplate', 'required'],
        ['fileName', 'required'],
        ['fileName', 'html_file_name'],
        ['fileName', 'html_file_extension'],
        ['fileName', 'file_in_dir', 'dir' => APP_DIR_NAME],
        ['fileName', 'file_exists', 'negate' => true],
        ['pageTitle', 'required'],
    ];
}

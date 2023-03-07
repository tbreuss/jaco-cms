<?php

declare(strict_types=1);

namespace jaco\api\page\create;

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
        return [response\UNPROCESSABLE_CONTENT, $errors];
    }

    // create page
    if (!page\create_from_template($data['pageTemplate'], $data['fileName'], $data['pageTitle'])) {
        return [response\INTERNAL_SERVER_ERROR, [['title' => 'Could not create page']]];
    }

    return [response\OK];
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['pageTemplate', 'jaco\data\filter\filename'],
        ['fileName', 'jaco\data\filter\filename'],
        ['pageTitle', 'jaco\data\filter\html_specialchars'],
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['pageTemplate', 'jaco\data\validator\required'],
        ['fileName', 'jaco\data\validator\required'],
        ['fileName', 'jaco\data\validator\htmlfile_name'],
        ['fileName', 'jaco\data\validator\htmlfile_extension'],
        ['fileName', 'jaco\data\validator\file_not_in_jaco_dir'],
        ['fileName', 'jaco\data\validator\file_not_exists'],
        ['pageTitle', 'jaco\data\validator\required'],
    ];
}

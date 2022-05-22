<?php

declare(strict_types=1);

namespace jaco\action\page\delete;

use jaco\filter;
use jaco\request;
use jaco\service\page;
use jaco\validate;

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
    if (!page\delete($data['fileName'])) {
        return [500, [['title' => 'Could not delete page']]];
    }

    return [200];
}

/**
 * @return string[][]
 */
function filters(): array
{
    return [
        ['fileName', 'filename']
    ];
}

/**
 * @return string[][]
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

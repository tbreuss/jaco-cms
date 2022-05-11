<?php

declare(strict_types=1);

namespace jaco\action\page\update;

use jaco\filter;
use jaco\request;
use jaco\validate;
use jaco\service\page;

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

    if (!page\update($data['fileName'], $data['html'])) {
        return [500, [['title' => 'Could not update page']]];
    }

    return [200];
}

/**
 * @return string[][]
 */
function filters(): array
{
    return [
        ['fileName', 'string'],
        ['fileName', 'filename'],
        ['html', 'string'],
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
        ['fileName', 'htmlfile_name'],
        ['fileName', 'htmlfile_extension'],
        ['html', 'required'],
    ];
}

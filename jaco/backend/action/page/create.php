<?php

declare(strict_types=1);

namespace jaco\action\page\create;

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
    $errors = validate\all($data, rules());
    if (count($errors) > 0) {
        return [422, $errors];
    }

    // create page
    if (!page\create_from_template($data['pageTemplate'], $data['fileName'], $data['pageTitle'])) {
        return [500, [['title' => 'Could not create page']]];
    }

    return [200];
}

/**
 * @return string[][]
 */
function rules(): array
{
    return [
        ['pageTemplate', 'required'],
        ['fileName', 'required'],
        ['fileName', 'htmlfile_name'],
        ['fileName', 'htmlfile_extension'],
        ['fileName', 'file_not_exists'],
        ['pageTitle', 'required'],
    ];
}

/**
 * @return string[][]
 */
function filters(): array
{
    return [
        ['pageTemplate', 'filename'],
        ['fileName', 'filename'],
        ['pageTitle', 'html_specialchars'],
    ];
}

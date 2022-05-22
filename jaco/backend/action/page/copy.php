<?php

declare(strict_types=1);

namespace jaco\action\page\copy;

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

    // copy page
    if (!page\copy($data['srcFileName'], $data['dstFileName'], $data['dstPageTitle'])) {
        return [500, [['title' => 'Could not copy page']]];
    }

    return [200];
}

/**
 * @return string[][]
 */
function filters(): array
{
    return [
        ['srcFileName', 'filename'],
        ['dstFileName', 'filename'],
        ['dstPageTitle', 'html_specialchars'],
    ];
}

/**
 * @return string[][]
 */
function validators(): array
{
    return [
        ['srcFileName', 'required'],
        ['srcFileName', 'file_exists'],
        ['dstFileName', 'required'],
        ['dstFileName', 'htmlfile_name'],
        ['dstFileName', 'htmlfile_extension'],
        ['dstFileName', 'file_not_exists'],
        ['dstPageTitle', 'required'],
    ];
}

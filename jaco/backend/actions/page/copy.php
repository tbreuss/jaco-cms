<?php

declare(strict_types=1);

namespace jaco\actions\page\copy;

use jaco\filter;
use jaco\request;
use jaco\validate;
use jaco\models\page;

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

    // copy page
    if (!page\copy($data['srcFileName'], $data['dstFileName'], $data['dstPageTitle'])) {
        return [500, ['Could not copy page']];
    }

    return [200];
}

function rules(): array
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

function filters(): array
{
    return [
        ['srcFileName', 'filename'],
        ['dstFileName', 'filename'],
        ['dstPageTitle', 'html_specialchars'],
    ];
}

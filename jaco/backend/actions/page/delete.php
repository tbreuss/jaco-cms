<?php

namespace jaco\actions\page\delete;

use jaco\filter;
use jaco\request;
use jaco\models\page;
use jaco\validate;

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

    // delete page
    if (!page\delete($data['fileName'])) {
        return [500, [['source' => ['pointer' => ''], 'detail' => 'Could not delete page']]];
    }

    return [200];
}

function rules(): array
{
    return [
        ['fileName', 'required'],
        ['fileName', 'file_exists'],
        ['fileName', 'file_writeable'],
        ['fileName', 'htmlfile_name'],
        ['fileName', 'htmlfile_extension']
    ];
}

function filters(): array
{
    return [
        ['fileName', 'filename']
    ];
}

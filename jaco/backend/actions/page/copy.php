<?php

namespace jaco\actions\page\copy;

use jaco\request;
use jaco\validate;
use jaco\models\page;

function main(): array
{
    $data = request\post_values();
    $errors = validate\all($data, rules());
    if (count($errors) > 0) {
        return [422, $errors];
    }
    if (!page\copy($data['srcFileName'], $data['dstFileName'], $data['dstPageTitle'])) {
        return [500, ['Could not copy page']];
    }
    return $data;
}

function rules(): array
{
    return [
        ['srcFileName', 'required'],
        ['srcFileName', 'file_exists'],
        ['dstFileName', 'required'],
        ['dstFileName', 'file_not_exists'],
        ['dstPageTitle', 'required'],
    ];
}

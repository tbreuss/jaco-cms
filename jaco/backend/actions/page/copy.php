<?php

namespace jaco\actions\page\copy;

use jaco\request;
use jaco\validate;

function main(): array
{
    $data = request\post_values();
    $errors = validate\validate_array($data, rules());
    if (count($errors) > 0) {
        return [422, $errors];
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

<?php

namespace jaco\actions\page\delete;

use jaco\request;
use jaco\models\page;
use jaco\validate;

function main(): array
{
    $data = request\post_values();
    $errors = validate\all($data, rules());
    if (count($errors) > 0) {
        return [400, $errors];
    }
    $fileName = $data['fileName'];
    if (!page\delete($fileName)) {
        return [500, ['Could not delete page']];
    }
    return [200];
}

function rules(): array
{
    return [
        ['fileName', 'required'],
        ['fileName', 'file_exists'],
        ['fileName', 'file_writeable'],
        ['fileName', 'file_html'],
    ];
}

<?php

namespace jaco\actions\page\copy;

use jaco\request;

function main(): array
{
    $data = request\post_values();
    $errors = validate($data);
    if (count($errors) > 0) {
        return [422, $errors];
    }
    return $data;
}

function validate(array $data): array
{
    $test = 1;
    // validate:
    // - srcFile not empty, does exist
    // - dstFile not empty, does not exist
    // - dstPage not empty
    return [
        'error' => 'Unknown error'
    ];
}

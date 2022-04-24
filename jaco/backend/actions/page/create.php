<?php

namespace jaco\actions\page\create;

use jaco\request;

function main(): array
{
    $data = request\post_values();
    $errors = validate($data);
    if (count($errors) > 0) {
        return [400, $errors];
    }
    return $data;
}

function validate(array $data): array
{
    return [
        'error' => 'Unknown error'
    ];
}

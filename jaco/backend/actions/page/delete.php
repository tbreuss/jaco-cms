<?php

namespace jaco\actions\page\delete;

use jaco\request;
use jaco\models\page;

function main(): array
{
    $fileName = request\post_value('fileName', '');
    $errors = validate($fileName);
    if (count($errors) > 0) {
        return [400, $errors];
    }
    if (!page\delete($fileName)) {
        return [500, ['Could not delete page']];
    }
    return [200];
}

function validate(string $fileName): array
{
    if (!page\is_valid($fileName)) {
        return [
            'error' => 'Error: fileName is invalid'
        ];
    }
    if (!page\is_writable($fileName)) {
        return [
            'error' => 'Error: fileName is not writable'
        ];
    }
    return [];
}

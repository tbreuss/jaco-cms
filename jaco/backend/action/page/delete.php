<?php

declare(strict_types=1);

namespace jaco\action\page\delete;

use jaco\data;
use jaco\request;
use jaco\service\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    // get raw user input
    $raw = request\post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());
    if (count($errors) > 0) {
        return [422, $errors];
    }

    // delete page
    if (!page\delete($data['fileName'])) {
        return [500, [['title' => 'Could not delete page']]];
    }

    return [200];
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['fileName', 'jaco\data\filter\filename']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['fileName', 'jaco\data\validator\required'],
        ['fileName', 'jaco\data\validator\file_exists'],
        ['fileName', 'jaco\data\validator\file_writeable'],
        ['fileName', 'jaco\data\validator\htmlfile_name'],
        ['fileName', 'jaco\data\validator\htmlfile_extension']
    ];
}

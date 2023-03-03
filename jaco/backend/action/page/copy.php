<?php

declare(strict_types=1);

namespace jaco\action\page\copy;

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

    // copy page
    if (!page\copy($data['srcFileName'], $data['dstFileName'], $data['dstPageTitle'])) {
        return [500, [['title' => 'Could not copy page']]];
    }

    return [200];
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['srcFileName', 'jaco\data\filter\filename'],
        ['dstFileName', 'jaco\data\filter\filename'],
        ['dstPageTitle', 'jaco\data\filter\html_specialchars'],
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['srcFileName', 'jaco\data\validator\required'],
        ['srcFileName', 'jaco\data\validator\file_exists'],
        ['dstFileName', 'jaco\data\validator\required'],
        ['dstFileName', 'jaco\data\validator\htmlfile_name'],
        ['dstFileName', 'jaco\data\validator\htmlfile_extension'],
        ['dstFileName', 'jaco\data\validator\file_not_in_jaco_dir'],
        ['dstFileName', 'jaco\data\validator\file_not_exists'],
        ['dstPageTitle', 'jaco\data\validator\required'],
    ];
}

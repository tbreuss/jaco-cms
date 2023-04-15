<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\data;
use jaco\request;
use jaco\response;
use jaco\service\page;
use jaco\validate;

use const jaco\APP_DIR_NAME;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/create/request.json')]
#[validate\response('/api/null.json')]
function action(array $apiInput): array
{
    $errors = data\validate($apiInput, validators());

    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    if (page\create_from_template($apiInput['pageTemplate'], $apiInput['fileName'], $apiInput['pageTitle'])) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not create page']]);
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['pageTemplate', 'filename'],
        ['fileName', 'filename'],
        ['pageTitle', 'html_specialchars'],
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['pageTemplate', 'required'],
        ['fileName', 'required'],
        ['fileName', 'html_file_name'],
        ['fileName', 'html_file_extension'],
        ['fileName', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['fileName', 'file_exists', 'negate' => true],
        ['pageTitle', 'required'],
    ];
}

<?php

declare(strict_types=1);

namespace jaco\api\page\delete;

use jaco\data;
use jaco\response;
use jaco\service\page;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/delete/request.json')]
#[validate\response('/api/null.json')]
function action(array $apiInput): array
{
    $errors = data\validate($apiInput, validators());

    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    if (page\delete($apiInput['fileName'])) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not delete page']]);
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['fileName', 'filename']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['fileName', 'required'],
        ['fileName', 'file_exists'],
        ['fileName', 'file_writeable'],
        ['fileName', 'html_file_name'],
        ['fileName', 'html_file_extension']
    ];
}

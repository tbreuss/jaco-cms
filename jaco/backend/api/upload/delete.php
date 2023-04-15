<?php

declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\data;
use jaco\response;
use jaco\service\upload;
use jaco\validate;

use const jaco\UPLOAD_EXTENSIONS;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/upload/delete/request.json')]
#[validate\response('/api/null.json')]
function action(array $apiInput): array
{
    $errors = data\validate($apiInput, validators());

    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    if (upload\delete($apiInput['fileName'])) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not delete upload']]);
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
        ['fileName', 'file_extension', 'allowedExtensions' => UPLOAD_EXTENSIONS]
    ];
}

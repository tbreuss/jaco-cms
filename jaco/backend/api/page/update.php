<?php

declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\data;
use jaco\html;
use jaco\response;
use jaco\service\page;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/update/request.json')]
#[validate\response('/api/null.json')]
function action(array $apiInput): array
{
    $errors = data\validate($apiInput, validators());

    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($apiInput['html']);

    if (!$status) {
        return response\internal_server_error(array_map(fn($error) => ['title' => $error], $htmlOrErrors));
    }

    if (page\update($apiInput['fileName'], $htmlOrErrors)) {
        return response\ok();
    }

    return response\internal_server_error([['title' => 'Could not update page']]);
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['fileName', 'string'],
        ['fileName', 'filename'],
        ['html', 'string'],
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
        ['fileName', 'html_file_name'],
        ['fileName', 'html_file_extension'],
        ['html', 'required'],
    ];
}

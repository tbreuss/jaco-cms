<?php

declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\data;
use jaco\html;
use jaco\request;
use jaco\response;
use jaco\service\page;

/**
 * @phpstan-return array<int,mixed>
 */
function action(): array
{
    $raw = request\get_post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());

    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($data['html']);

    if (!$status) {
        return response\internal_server_error(array_map(fn($error) => ['title' => $error], $htmlOrErrors));
    }

    if (page\update($data['fileName'], $htmlOrErrors)) {
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
        ['fileName', 'jaco\data\filter\string'],
        ['fileName', 'jaco\data\filter\filename'],
        ['html', 'jaco\data\filter\string'],
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
        ['fileName', 'jaco\data\validator\htmlfile_name'],
        ['fileName', 'jaco\data\validator\htmlfile_extension'],
        ['html', 'jaco\data\validator\required'],
    ];
}

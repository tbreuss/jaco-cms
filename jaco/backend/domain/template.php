<?php

declare(strict_types=1);

namespace jaco\domain\template;

use jaco\data;
use jaco\domain;
use jaco\filesystem;
use jaco\html;
use jaco\struct\file;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

/**
 * @return array{string, array}
 */
function page_templates(array $input): array
{
    $data = data\filter($input, []);

    $errors = data\validate($data, []);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return domain\error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: TEMPLATE_PAGE_DIR,
        allowedExtensions: PAGE_EXTENSIONS,
        withContent: true
    );

    return domain\success(
        array_map(fn(file $file): array => [
            'file' => $file->file,
            'title' => html\get_title($file->content),
            'created' => $file->modified
        ], $files)
    );
}

/**
 * @return array{string, array}
 */
function content_templates(): array
{
    return domain\error([['', 'TODO: implement this.']]); // TODO
}

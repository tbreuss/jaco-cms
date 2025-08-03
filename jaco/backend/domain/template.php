<?php

declare(strict_types=1);

namespace jaco\domain\template;

use jaco\lib\data;
use jaco\lib\filesystem;
use jaco\lib\html;
use jaco\lib\payload;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

/**
 * @return array{string, array}
 */
function page_templates(array $input): array
{
    $data = data\sanitize($input, []);

    $errors = data\validate($data, []);

    if (count($errors) > 0) {
        return payload\invalid($errors);
    }

    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return payload\error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: TEMPLATE_PAGE_DIR,
        allowedExtensions: PAGE_EXTENSIONS,
        withContent: true
    );

    return payload\success(
        array_map(fn(filesystem\file $file): array => [
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
    return payload\error([['', 'TODO: implement this.']]); // TODO
}

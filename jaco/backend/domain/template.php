<?php

declare(strict_types=1);

namespace jaco\domain\template;

use jaco\lib\data;
use jaco\lib\filesystem;
use jaco\lib\html;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

class TemplatePayload extends \jaco\domain\Payload
{}

function page_templates(array $input): TemplatePayload
{
    $data = data\sanitize($input, []);

    $errors = data\validate($data, []);

    if (count($errors) > 0) {
        return TemplatePayload::invalid($errors);
    }

    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return TemplatePayload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: TEMPLATE_PAGE_DIR,
        allowedExtensions: PAGE_EXTENSIONS,
        withContent: true
    );

    return TemplatePayload::success(
        array_map(fn(filesystem\file $file): array => [
            'file' => $file->file,
            'title' => html\get_title($file->content),
            'created' => $file->modified
        ], $files)
    );
}

function content_templates(): TemplatePayload
{
    return TemplatePayload::error([['', 'TODO: implement this.']]); // TODO
}

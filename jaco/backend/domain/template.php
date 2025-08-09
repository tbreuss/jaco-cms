<?php

declare(strict_types=1);

namespace jaco\domain\template;

use jaco\lib\filesystem;
use jaco\lib\html;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

final class DomainPayload extends \jaco\domain\Payload
{}

final class Template
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
    ) {
    }
}

function find_page_templates(): DomainPayload
{
    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: TEMPLATE_PAGE_DIR,
        allowedExtensions: PAGE_EXTENSIONS,
        withContent: true
    );

    return DomainPayload::success(
        array_map(fn(filesystem\File $file): Template => new Template(
            file: $file->file,
            title: html\get_title($file->content),
            created: $file->modified,
        ), $files)
    );
}

function find_content_templates(): DomainPayload
{
    return DomainPayload::error([['', 'TODO: implement this.']]); // TODO
}

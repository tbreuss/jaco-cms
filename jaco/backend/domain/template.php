<?php declare(strict_types=1);

namespace jaco\domain\template;

use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_CONTENT_DIR;
use const jaco\TEMPLATE_PAGE_DIR;

final class DomainPayload extends \jaco\domain\Payload {}

final class Template
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
    ) {}
}

function find_page_templates(): DomainPayload
{
    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: TEMPLATE_PAGE_DIR,
        allowedExtensions: PAGE_EXTENSIONS,
        withContent: true,
    );

    return DomainPayload::success(
        array_map(fn(filesystem\File $file): Template => new Template(
            file: $file->file,
            title: html\get_title($file->content),
            created: $file->modified,
        ), $files),
    );
}

function find_content_templates(): DomainPayload
{
    if (!filesystem\dir_exists(TEMPLATE_CONTENT_DIR)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: TEMPLATE_CONTENT_DIR,
        allowedExtensions: PAGE_EXTENSIONS,
        withContent: true,
    );

    return DomainPayload::success(
        array_map(function (filesystem\File $file): Template {
            $title = html\get_title($file->content);
            return new Template(
                file: $file->file,
                title: $title !== '' ? $title : $file->file,
                created: $file->modified,
            );
        }, $files),
    );
}

function find_content_template(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => TEMPLATE_CONTENT_DIR],
        ['file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS)],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file(TEMPLATE_CONTENT_DIR, $file);

    if ($html === null) {
        return DomainPayload::error([['file', 'Could not read file.']]);
    }

    $title = html\get_title($html);

    return DomainPayload::success([
        'file' => $file,
        'title' => $title !== '' ? $title : $file,
        'created' => filesystem\file_mtime($file, TEMPLATE_CONTENT_DIR),
        'content' => $html,
    ]);
}

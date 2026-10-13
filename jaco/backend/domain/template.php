<?php declare(strict_types=1);

namespace jaco\domain\template;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\domain\Payload;

final class Template
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
    ) {}
}

function find_page_templates(Config $config): Payload
{
    if (!filesystem\dir_exists($config->templatePageDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: $config->templatePageDir,
        allowedExtensions: $config->pageExtensions,
        withContent: true,
    );

    return Payload::success(
        array_map(fn(filesystem\File $file): Template => new Template(
            file: $file->file,
            title: html\get_title($file->content),
            created: $file->modified,
        ), $files),
    );
}

function find_content_templates(Config $config): Payload
{
    if (!filesystem\dir_exists($config->templateContentDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: $config->templateContentDir,
        allowedExtensions: ['html'],
        withContent: true,
    );

    return Payload::success(
        array_map(function (filesystem\File $file): Template {
            // fragments carry no title, so the file name is used
            $title = html\get_title((string) $file->content);
            return new Template(
                file: $file->file,
                title: $title !== '' ? $title : $file->file,
                created: $file->modified,
            );
        }, $files),
    );
}

function find_content_template(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->templateContentDir],
        ['html_file_name'],
        ['file_extension', 'allowedExtensions' => ['html']],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $content = filesystem\read_file($config->templateContentDir, $file);

    if ($content === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    return Payload::success([
        'file' => $file,
        'created' => filesystem\file_mtime($file, $config->templateContentDir),
        'content' => $content,
    ]);
}

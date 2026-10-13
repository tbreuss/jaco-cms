<?php declare(strict_types=1);

namespace jaco\domain\page;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;
use jaco\domain\Payload;

const SORT_PARAMETERS = ['created', 'file', 'title'];

final class Page
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
        public ?string $content,
    ) {}
}


function find_pages(Config $config, string $filteredBy, string $sortedBy): Payload
{
    $errors = data\validate(
        [
            'filteredBy' => $filteredBy,
            'sortedBy' => $sortedBy,
        ],
        [
            ['filteredBy', 'is_string'],
            ['sortedBy', 'is_string'],
            ['sortedBy', 'is_sort_parameter', 'fields' => SORT_PARAMETERS],
        ],
    );

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $files = filesystem\find_files_rec($config->webDir, $config->pageExtensions, [$config->appDir]);

    $sorted = list\parse_sort($sortedBy, SORT_PARAMETERS);
    $pages = create_list($files, $config->webDir);
    $pages = list\filter_list($pages, $filteredBy);
    $pages = list\sort_list($pages, $sorted);

    return Payload::success([
        'items' => $pages,
        'filter' => $filteredBy,
        'sort' => $sorted,
    ]);
}

function find_page(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->webDir],
        ['has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $file);

    if ($html === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    return Payload::success([
        'file' => $file,
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($file, $config->webDir),
        'content' => $html,
    ]);
}

function delete_page(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->webDir],
        ['is_file_writable', 'dir' => $config->webDir],
        ['has_html_file_name'],
        ['has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $status = filesystem\backup_file($config->webDir, $file, $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file($config->webDir, $file);

    if (!$status) {
        return Payload::error([['file', 'Could not delete file.']]);
    }

    return Payload::success();
}

function copy_page(Config $config, string $file, string $toFile, string $withTitle): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'toFile' => $toFile,
            'withTitle' => $withTitle,
        ],
        [
            ['file', 'to_filename'],
            ['toFile', 'to_filename'],
            ['withTitle', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['toFile', 'is_required'],
        ['toFile', 'has_html_file_name'],
        ['toFile', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['toFile', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['toFile', 'is_file_present', 'dir' => $config->webDir, 'negate' => true],
        ['withTitle', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $data['file']);

    if ($html === null) {
        return Payload::error([['file', 'Could not read file content.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file($config, $config->webDir, $data['toFile'], $html);

    if ($status === false) {
        return Payload::error([['toFile', 'Could not write file content.']]);
    }

    $html = filesystem\read_file($config->webDir, $data['toFile']);

    if ($html === null) {
        return Payload::error([['toFile', 'Could not read new page.']]);
    }

    return Payload::success([
        'file' => $data['toFile'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['toFile'], $config->webDir),
        'content' => $html,
    ]);
}

function create_page(Config $config, string $file, string $fromTemplate, string $withTitle): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'fromTemplate' => $fromTemplate,
            'withTitle' => $withTitle,
        ],
        [
            ['fromTemplate', 'to_filename'],
            ['file', 'to_filename'],
            ['withTitle', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['file', 'is_file_present', 'dir' => $config->webDir, 'negate' => true],
        ['fromTemplate', 'is_required'],
        ['withTitle', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->templatePageDir, $data['fromTemplate']);

    if ($html === null) {
        return Payload::error([['fromTemplate', 'Could not read file.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $html);

    if (!$status) {
        return Payload::error([['file', 'Could not write file.']]);
    }

    return Payload::success();
}

function update_page(Config $config, string $file, string $withContent): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'withContent' => $withContent,
        ],
        [
            ['file', 'to_string'],
            ['file', 'to_filename'],
            ['withContent', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['withContent', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($config, $data['withContent']);

    if ($status) {
        // the page manager owns the document: doctype and head stay as sent,
        // only the body is treated as content and sanitized
        $data['withContent'] = html\replace_body($data['withContent'], html\sanitize_html($htmlOrErrors));
    } else {
        return Payload::error([['withContent', \reset($htmlOrErrors)]]);
    }

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $data['withContent']);

    if (!$status) {
        return Payload::error([['file', 'Could not write content to file.']]);
    }

    return Payload::success();
}

/**
 * Update only the body of a page. The inline editor sends content, never a document,
 * so the document around it - doctype and head - stays untouched.
 */
function update_page_body(Config $config, string $file, string $withBody): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'withBody' => $withBody,
        ],
        [
            ['file', 'to_string'],
            ['file', 'to_filename'],
            ['withBody', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['withBody', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($config, $data['withBody']);

    if (!$status) {
        return Payload::error([['withBody', \reset($htmlOrErrors)]]);
    }

    // the client owns the content, the stored document keeps doctype and head
    $document = filesystem\read_file($config->webDir, $data['file']) ?? '';
    $data['withBody'] = html\replace_body($document, html\sanitize_html($htmlOrErrors));

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $data['withBody']);

    if (!$status) {
        return Payload::error([['file', 'Could not write content to file.']]);
    }

    return Payload::success();
}

/**
 * @param filesystem\File[] $files
 * @return Page[]
 */
function create_list(array $files, string $dir): array
{
    $pages = [];
    foreach ($files as $file) {
        $pages[] = create_one($dir, $file->file, $file->modified);
    }
    return $pages;
}

function create_one(string $dir, string $file, int $modified, ?string $content = null): Page
{
    $html = filesystem\read_file($dir, $file);

    return new Page(
        $file,
        html\get_title($html),
        $modified,
        $content,
    );
}

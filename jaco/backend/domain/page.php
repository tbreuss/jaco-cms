<?php declare(strict_types=1);

namespace jaco\domain\page;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;

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

final class DomainPayload extends \jaco\domain\Payload {}

function find_pages(Config $config, string $filteredBy, string $sortedBy): DomainPayload
{
    $errors = data\validate(
        [
            'filteredBy' => $filteredBy,
            'sortedBy' => $sortedBy,
        ],
        [
            ['filteredBy', 'string'],
            ['sortedBy', 'string'],
            ['sortedBy', 'sort_parameter', 'fields' => SORT_PARAMETERS],
        ],
    );

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $files = filesystem\find_files_rec($config->webDir, $config->pageExtensions, [$config->appDir]);

    $sorted = sort_field($sortedBy);
    $pages = create_list($files, $config->webDir);
    $pages = list\filter_list($pages, $filteredBy);
    $pages = list\sort_list($pages, $sorted);

    return DomainPayload::success([
        'items' => $pages,
        'filter' => $filteredBy,
        'sort' => $sorted,
    ]);
}

function find_page(Config $config, string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->webDir],
        ['file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $file);

    if ($html === null) {
        return DomainPayload::error([['file', 'Could not read file.']]);
    }

    return DomainPayload::success([
        'file' => $file,
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($file, $config->webDir),
        'content' => $html,
    ]);
}

function delete_page(Config $config, string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->webDir],
        ['file_writeable', 'dir' => $config->webDir],
        ['html_file_name'],
        ['html_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $status = filesystem\backup_file($config->webDir, $file, $config->backupDir);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file($config->webDir, $file);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not delete file.']]);
    }

    return DomainPayload::success();
}

function copy_page(Config $config, string $file, string $toFile, string $withTitle): DomainPayload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'toFile' => $toFile,
            'withTitle' => $withTitle,
        ],
        [
            ['file', 'filename'],
            ['toFile', 'filename'],
            ['withTitle', 'string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => $config->webDir],
        ['file', 'file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['toFile', 'required'],
        ['toFile', 'html_file_name'],
        ['toFile', 'html_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['toFile', 'file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['toFile', 'file_exists', 'dir' => $config->webDir, 'negate' => true],
        ['withTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $data['file']);

    if ($html === null) {
        return DomainPayload::error([['file', 'Could not read file content.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file($config, $config->webDir, $data['toFile'], $html);

    if ($status === false) {
        return DomainPayload::error([['toFile', 'Could not write file content.']]);
    }

    $html = filesystem\read_file($config->webDir, $data['toFile']);

    if ($html === null) {
        return DomainPayload::error([['toFile', 'Could not read new page.']]);
    }

    return DomainPayload::success([
        'file' => $data['toFile'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['toFile'], $config->webDir),
        'content' => $html,
    ]);
}

function create_page(Config $config, string $file, string $fromTemplate, string $withTitle): DomainPayload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'fromTemplate' => $fromTemplate,
            'withTitle' => $withTitle,
        ],
        [
            ['fromTemplate', 'filename'],
            ['file', 'filename'],
            ['withTitle', 'string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['file', 'file_exists', 'dir' => $config->webDir, 'negate' => true],
        ['fromTemplate', 'required'],
        ['withTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file($config->templatePageDir, $data['fromTemplate']);

    if ($html === null) {
        return DomainPayload::error([['fromTemplate', 'Could not read file.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $html);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not write file.']]);
    }

    return DomainPayload::success();
}

function update_page(Config $config, string $file, string $withContent): DomainPayload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'withContent' => $withContent,
        ],
        [
            ['file', 'string'],
            ['file', 'filename'],
            ['withContent', 'string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => $config->webDir],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['withContent', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($config, $data['withContent']);

    if ($status) {
        // the page manager owns the document: doctype and head stay as sent,
        // only the body is treated as content and sanitized
        $data['withContent'] = html\replace_body($data['withContent'], html\sanitize_html($htmlOrErrors));
    } else {
        return DomainPayload::error([['withContent', \reset($htmlOrErrors)]]);
    }

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $data['withContent']);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not write content to file.']]);
    }

    return DomainPayload::success();
}

/**
 * Update only the body of a page. The inline editor sends content, never a document,
 * so the document around it - doctype and head - stays untouched.
 */
function update_page_body(Config $config, string $file, string $withBody): DomainPayload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'withBody' => $withBody,
        ],
        [
            ['file', 'string'],
            ['file', 'filename'],
            ['withBody', 'string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => $config->webDir],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['withBody', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($config, $data['withBody']);

    if (!$status) {
        return DomainPayload::error([['withBody', \reset($htmlOrErrors)]]);
    }

    // the client owns the content, the stored document keeps doctype and head
    $document = filesystem\read_file($config->webDir, $data['file']) ?? '';
    $data['withBody'] = html\replace_body($document, html\sanitize_html($htmlOrErrors));

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $data['withBody']);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not write content to file.']]);
    }

    return DomainPayload::success();
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

/**
 * @return array{string, string}
 */
function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), SORT_PARAMETERS)) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

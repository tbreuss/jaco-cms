<?php declare(strict_types=1);

namespace jaco\domain\page;

use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

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

function find_pages(string $filteredBy, string $sortedBy): DomainPayload
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

    $files = filesystem\find_files_rec(WEB_DIR, PAGE_EXTENSIONS, [APP_DIR]);

    $sorted = sort_field($sortedBy);
    $pages = create_list($files, WEB_DIR);
    $pages = list\filter_list($pages, $filteredBy);
    $pages = list\sort_list($pages, $sorted);

    return DomainPayload::success([
        'items' => $pages,
        'filter' => $filteredBy,
        'sort' => $sorted,
    ]);
}

function find_page(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => WEB_DIR],
        ['file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS)],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file(WEB_DIR, $file);

    if ($html === null) {
        return DomainPayload::error([['file', 'Could not read file.']]);
    }

    return DomainPayload::success([
        'file' => $file,
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($file, WEB_DIR),
        'content' => $html,
    ]);
}

function delete_page(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => WEB_DIR],
        ['file_writeable'],
        ['html_file_name'],
        ['html_file_extension'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file(WEB_DIR, $file);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not delete file.']]);
    }

    return DomainPayload::success();
}

function copy_page(string $file, string $toFile, string $withTitle): DomainPayload
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
        ['file', 'file_exists'],
        ['toFile', 'required'],
        ['toFile', 'html_file_name'],
        ['toFile', 'html_file_extension'],
        ['toFile', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['toFile', 'file_exists', 'negate' => true],
        ['withTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file(WEB_DIR, $data['file']);

    if ($html === null) {
        return DomainPayload::error([['file', 'Could not read file content.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['toFile'], $html);

    if ($status === false) {
        return DomainPayload::error([['toFile', 'Could not write file content.']]);
    }

    $html = filesystem\read_file(WEB_DIR, $data['toFile']);

    if ($html === null) {
        return DomainPayload::error([['toFile', 'Could not read new page.']]);
    }

    return DomainPayload::success([
        'file' => $data['toFile'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['toFile'], WEB_DIR),
        'content' => $html,
    ]);
}

function create_page(string $file, string $fromTemplate, string $withTitle): DomainPayload
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
        ['file', 'html_file_extension'],
        ['file', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['file', 'file_exists', 'negate' => true],
        ['fromTemplate', 'required'],
        ['withTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $html = filesystem\read_file(TEMPLATE_PAGE_DIR, $data['fromTemplate']);

    if ($html === null) {
        return DomainPayload::error([['fromTemplate', 'Could not read file.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['file'], $html);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not write file.']]);
    }

    return DomainPayload::success();
}

function update_page(string $file, string $withContent): DomainPayload
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
        ['file', 'file_exists'],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension'],
        ['withContent', 'required'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($data['withContent']);

    if ($status) {
        $data['withContent'] = $htmlOrErrors;
    } else {
        return DomainPayload::error([['withContent', \reset($htmlOrErrors)]]);
    }

    $status = filesystem\backup_file(WEB_DIR, $data['file'], BACKUP_DIR);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file(WEB_DIR, $data['file'], $data['withContent']);

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

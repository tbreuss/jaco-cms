<?php

declare(strict_types=1);

namespace jaco\domain\page;

use jaco\data;
use jaco\domain;
use jaco\filesystem;
use jaco\html;
use jaco\list;
use jaco\struct\file;
use jaco\struct\page;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

/**
 * @return array{string, array}
 */
function find_all(string $filteredBy, string $sortedBy): array
{
    $errors = data\validate(
        [
            'filteredBy' => $filteredBy,
            'sortedBy' => $sortedBy,
        ],
        [
            ['filteredBy', 'string'],
            ['sortedBy', 'string'],
        ]
    );

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    $files = filesystem\find_files_rec(WEB_DIR, PAGE_EXTENSIONS, [APP_DIR]);
    $pages = _create_list($files, WEB_DIR);
    $pages = list\filter($pages, $filteredBy);
    $sortedBy = _sort_field($sortedBy);
    $output = [list\sort($pages, $sortedBy), $filteredBy, $sortedBy];

    return domain\success($output);
}

/**
 * @return array{string, array}
 */
function find(string $file): array
{
    $file = data\filter_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => WEB_DIR],
        ['file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS)],
    ]);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    $html = filesystem\read_file(WEB_DIR, $file);

    if ($html === null) {
        return domain\error([['file', 'Could not read file.']]);
    }

    return domain\success([
        'file' => $file,
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($file, WEB_DIR),
        'content' => $html
    ]);
}

/**
 * @return array{string, array}
 */
function delete(string $file): array
{
    $file = data\filter_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => WEB_DIR],
        ['file_writeable'],
        ['html_file_name'],
        ['html_file_extension']
    ]);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);

    if (!$status) {
        return domain\error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file(WEB_DIR, $file);

    if (!$status) {
        return domain\error([['file', 'Could not delete file.']]);
    }

    return domain\success(null);
}

/**
 * @return array{string, array}
 */
function copy(string $file, string $toFile, string $withTitle): array
{
    $data = data\filter(
        [
            'file' => $file,
            'toFile' => $toFile,
            'withTitle' => $withTitle,
        ],
        [
            ['file', 'filename'],
            ['toFile', 'filename'],
            ['withTitle', 'string'],
        ]
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
        return domain\invalid($errors);
    }

    $html = filesystem\read_file(WEB_DIR, $data['file']);

    if ($html === null) {
        return domain\error([['file', 'Could not read file content.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['toFile'], $html);

    if ($status === false) {
        return domain\error([['toFile', 'Could not write file content.']]);
    }

    $html = filesystem\read_file(WEB_DIR, $data['toFile']);

    if ($html === null) {
        return domain\error([['toFile', 'Could not read new page.']]);
    }

    return domain\success([
        'file' => $data['toFile'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['toFile'], WEB_DIR),
        'content' => $html
    ]);
}

/**
 * @return array{string, array}
 */
function create(string $file, string $fromTemplate, string $withTitle): array
{
    $data = data\filter(
        [
            'file' => $file,
            'fromTemplate' => $fromTemplate,
            'withTitle' => $withTitle
        ],
        [
            ['fromTemplate', 'filename'],
            ['file', 'filename'],
            ['withTitle', 'string'],
        ]
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
        return domain\invalid($errors);
    }

    $html = filesystem\read_file(TEMPLATE_PAGE_DIR, $data['fromTemplate']);

    if ($html === null) {
        return domain\error([['fromTemplate', 'Could not read file.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['file'], $html);

    if (!$status) {
        return domain\error([['file', 'Could not write file.']]);
    }

    return domain\success(null);
}

/**
 * @return array{string, array}
 */
function update(string $file, string $withContent): array
{
    $data = data\filter(
        [
            'file' => $file,
            'withContent' => $withContent
        ],
        [
            ['file', 'string'],
            ['file', 'filename'],
            ['withContent', 'string'],
        ]
    );

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists'],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension'],
        ['withContent', 'required'],
    ]);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($data['withContent']);

    if ($status) {
        $data['withContent'] = $htmlOrErrors;
    } else {
        return domain\error([['withContent', \reset($htmlOrErrors)]]);
    }

    $status = filesystem\backup_file(WEB_DIR, $data['file'], BACKUP_DIR);

    if (!$status) {
        return domain\error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file(WEB_DIR, $data['file'], $data['withContent']);

    if (!$status) {
        return domain\error([['file', 'Could not write content to file.']]);
    }

    return domain\success(null);
}

/**
 * @param file[] $files
 * @return page[]
 */
function _create_list(array $files, string $dir): array
{
    $pages = [];
    foreach ($files as $file) {
        $pages[] = _create_one($dir, $file->file, $file->modified);
    }
    return $pages;
}

function _create_one(string $dir, string $file, int $modified, ?string $content = null): page
{
    $html = filesystem\read_file($dir, $file);

    return new page(
        $file,
        html\get_title($html),
        $modified,
        $content
    );
}

/**
 * @return array{string, string}
 */
function _sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), ['created', 'file', 'title'])) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

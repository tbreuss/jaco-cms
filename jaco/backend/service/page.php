<?php

declare(strict_types=1);

namespace jaco\service\page;

use jaco\data;
use jaco\filesystem;
use jaco\html;
use jaco\list;
use jaco\service;
use jaco\struct\file;
use jaco\struct\page;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

function find_all(array $input): array
{
    $data = data\filter($input, [
        ['filter', 'safe'],
        ['sort', 'safe'],
    ]);

    $errors = data\validate($data, [
        ['filter', 'string'],
        ['sort', 'string'],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    $files = filesystem\find_files_rec(WEB_DIR, PAGE_EXTENSIONS, [APP_DIR]);
    $pages = _create_list($files, WEB_DIR);
    $pages = list\filter($pages, $data['filter']);
    $sort = _sort_field($data['sort']);
    $output = [list\sort($pages, $sort), $data['filter'], $sort];

    return service\success($output);
}

function find_one(array $input): array
{
    $data = data\filter($input, [
        ['file', 'filename'],
    ]);

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => WEB_DIR],
        ['file', 'file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS)],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    $html = filesystem\read_file(WEB_DIR, $data['file']);

    if ($html === null) {
        return service\error([['file', 'Could not read file.']]);
    }

    return service\success([
        'file' => $data['file'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['file'], WEB_DIR),
        'content' => $html
    ]);
}

function delete(array $input): array
{
    $data = data\filter($input, [
        ['file', 'filename'],
    ]);

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => WEB_DIR],
        ['file', 'file_writeable'],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension']
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    $status = filesystem\backup_file(WEB_DIR, $data['file'], BACKUP_DIR);

    if (!$status) {
        return service\error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file(WEB_DIR, $data['file']);

    if (!$status) {
        return service\error([['file', 'Could not delete file.']]);
    }

    return service\success(null);
}

/**
 * @param array{srcFile: string, dstFile: string, dstTitle: string} $input
 * @return array{0: string, 1: array}
 */
function copy(array $input): array
{
    $data = data\filter($input, [
        ['srcFile', 'filename'],
        ['dstFile', 'filename'],
        ['dstTitle', 'string'],
    ]);

    $errors = data\validate($data, [
        ['srcFile', 'required'],
        ['srcFile', 'file_exists'],
        ['dstFile', 'required'],
        ['dstFile', 'html_file_name'],
        ['dstFile', 'html_file_extension'],
        ['dstFile', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['dstFile', 'file_exists', 'negate' => true],
        ['dstTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    $html = filesystem\read_file(WEB_DIR, $data['srcFile']);

    if ($html === null) {
        return service\error([['srcFile', 'Could not read file content.']]);
    }

    $html = html\set_title($html, $data['dstTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['dstFile'], $html);

    if ($status === false) {
        return service\error([['dstFile', 'Could not write file content.']]);
    }

    $html = filesystem\read_file(WEB_DIR, $data['dstFile']);

    if ($html === null) {
        return service\error([['dstFile', 'Could not read new page.']]);
    }

    return service\success([
        'file' => $data['dstFile'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['dstFile'], WEB_DIR),
        'content' => $html
    ]);
}

function create_from_template(array $input): array
{
    $data = data\filter($input, [
        ['srcFile', 'filename'],
        ['dstFile', 'filename'],
        ['dstTitle', 'string'],
    ]);

    $errors = data\validate($data, [
        ['srcFile', 'required'],
        ['dstFile', 'required'],
        ['dstFile', 'html_file_name'],
        ['dstFile', 'html_file_extension'],
        ['dstFile', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['dstFile', 'file_exists', 'negate' => true],
        ['dstTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    $html = filesystem\read_file(TEMPLATE_PAGE_DIR, $data['srcFile']);

    if ($html === null) {
        return service\error([['srcFile', 'Could not read file.']]);
    }

    $html = html\set_title($html, $data['dstTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['dstFile'], $html);

    if (!$status) {
        return service\error([['dstFile', 'Could not write file.']]);
    }

    return service\success(null);
}

function update(array $input): array
{
    $data = data\filter($input, [
        ['file', 'string'],
        ['file', 'filename'],
        ['content', 'string'],
    ]);

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists'],
        ['file', 'html_file_name'],
        ['file', 'html_file_extension'],
        ['content', 'required'],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($data['content']);

    if ($status) {
        $data['content'] = $htmlOrErrors;
    } else {
        return service\error([['content', \reset($htmlOrErrors)]]);
    }

    $status = filesystem\backup_file(WEB_DIR, $data['file'], BACKUP_DIR);

    if (!$status) {
        return service\error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file(WEB_DIR, $data['file'], $data['content']);

    if (!$status) {
        return service\error([['file', 'Could not write content to file.']]);
    }

    return service\success(null);
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

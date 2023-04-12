<?php

declare(strict_types=1);

namespace jaco\service\page;

use jaco\data;
use jaco\filesystem;
use jaco\list;
use jaco\html;
use jaco\struct\file;
use jaco\struct\page;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

/**
 * @phpstan-return array{0:page[],1:string,2:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $files = filesystem\find_files_rec(WEB_DIR, PAGE_EXTENSIONS, [APP_DIR]);
    $pages = create_list($files, WEB_DIR);
    $pages = list\filter($pages, $filter);
    $sort = sort_field($sort);
    return [list\sort($pages, $sort), $filter, $sort];
}

function find_one(string $file): ?page
{
    $html = filesystem\read_file(WEB_DIR, $file);

    if ($html) {
        return new page(
            $file,
            html\get_title($html),
            filesystem\file_mtime($file, WEB_DIR),
            $html
        );
    }

    return null;
}

function delete(string $file): bool
{
    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);
    if (!$status) {
        return false;
    }
    return filesystem\delete_file(WEB_DIR, $file);
}

function copy(string $srcFile, string $dstFile, string $dstPageTitle): array
{
    $srcFile = filesystem\filter_filename($srcFile);
    $dstFile = filesystem\filter_filename($dstFile);

    $html = filesystem\read_file(WEB_DIR, $srcFile);

    if ($html === null) {
        return [false, 'Source file does not exist.'];
    }

    if (!filesystem\validate_filename(WEB_DIR, $dstFile)) {
        return [false, 'Destination filename is invalid.'];
    }

    if (!filesystem\validate_extension(WEB_DIR, $dstFile)) {
        return [false, 'Destination file extension is invalid.'];
    }

    if (filesystem\file_in_dir($dstFile, APP_DIR_NAME)) {
        return [false, sprintf('Destination file must not be in the protected %s folder.', APP_DIR_NAME)];
    }

    if (filesystem\file_exists(WEB_DIR, $dstFile)) {
        return [false, 'Destination file already exists.'];
    }

    $html = html\set_title($html, $dstPageTitle);

    $status = filesystem\write_file(WEB_DIR, $dstFile, $html);

    return $status ? [true, ''] : [false, 'Could not write file'];
}

function copy_by_args(string $srcFile, string $dstFile, string $dstTitle): array
{
    return copy_by_array([
        'srcFile' => $srcFile,
        'dstFile' => $dstFile,
        'dstTitle' => $dstTitle,
    ]);
}

function copy_by_array(array $input): array
{
    $data = data\filter($input, [
        ['srcFile', 'filename'],
        ['dstFile', 'filename'],
        ['dstTitle', 'string'],
    ]);

    $errors = data\validate2($data, [
        ['srcFile', 'required'],
        ['srcFile', 'file_exists'],
        ['dstFile', 'required'],
        ['dstFile', 'htmlfile_name'],
        ['dstFile', 'htmlfile_extension'],
        ['dstFile', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['dstFile', 'file_exists', 'negate' => true],
        ['dstTitle', 'required'],
    ]);

    if (count($errors) > 0) {
        return [false, $errors];
    }

    $html = filesystem\read_file(WEB_DIR, $data['srcFile']);

    if ($html === null) {
        return [false, [['srcFile', 'Could not read file content.']]];
    }

    $html = html\set_title($html, $data['dstTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['dstFile'], $html);

    if ($status === false) {
        return [false, [['dstFile', 'Could not write file content.']]];
    }

    $page = find_one($data['dstFile']);

    if ($page === null) {
        return [false, [['dstFile', 'Could not read new page.']]];
    }

    return [true, $page];
}

#[\validate_input('/service/page/copy/input.json')]
#[\validate_output('/service/page/copy/output.json')]
function copy_by_service(array $input, array $inputErrors = []): array
{
    $data = data\filter($input, [
        ['srcFile', 'filename'],
        ['dstFile', 'filename'],
        ['dstTitle', 'string'],
    ]);

    $validationErrors = data\validate2($data, [
        ['srcFile', 'file_exists'],
        ['dstFile', 'htmlfile_name'],
        ['dstFile', 'htmlfile_extension'],
        ['dstFile', 'file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
        ['dstFile', 'file_exists', 'negate' => true],
    ]);

    $errors = array_merge($inputErrors, $validationErrors);

    if (count($errors) > 0) {
        return [false, $errors];
    }

    $html = filesystem\read_file(WEB_DIR, $data['srcFile']);

    if ($html === null) {
        return [false, [['srcFile', 'Could not read file content.']]];
    }

    $html = html\set_title($html, $data['dstTitle']);

    $status = filesystem\write_file(WEB_DIR, $data['dstFile'], $html);

    if ($status === false) {
        return [false, [['dstFile', 'Could not write file content.']]];
    }

    $page = find_one($data['dstFile']);

    if ($page === null) {
        return [false, [['dstFile', 'Could not read new page.']]];
    }

    return [true, $page];
}

function create_from_template(string $srcFile, string $dstFile, string $dstPageTitle): bool
{
    $html = filesystem\read_file(TEMPLATE_PAGE_DIR, $srcFile);

    if ($html === null) {
        return false;
    }

    $html = html\set_title($html, $dstPageTitle);

    return filesystem\write_file(WEB_DIR, $dstFile, $html);
}

function update(string $file, string $content): bool
{
    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);

    if (!$status) {
        return false;
    }

    return filesystem\write_file(WEB_DIR, $file, $content);
}

/**
 * @param file[] $files
 * @return page[]
 */
function create_list(array $files, string $dir): array
{
    $pages = [];
    foreach ($files as $file) {
        $pages[] = create_one($dir, $file->file, $file->modified);
    }
    return $pages;
}

function create_one(string $dir, string $file, int $modified, ?string $content = null): page
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
function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), ['created', 'file', 'title'])) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

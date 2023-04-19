<?php

declare(strict_types=1);

namespace jaco\service\upload;

use jaco\data;
use jaco\filesystem;
use jaco\list;
use jaco\service;
use jaco\struct\file;
use jaco\struct\upload;

use const jaco\APP_DIR;
use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_EXTENSIONS;
use const jaco\WEB_DIR;

function find_all(array $input): array
{
    $data = data\filter($input, [
        ['filter', 'safe'],
        ['sort', 'safe']
    ]);

    $errors = data\validate($data, [
        ['filter', 'string'],
        ['sort', 'string'],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    if (!filesystem\dir_exists(WEB_DIR)) {
        return service\error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files_rec(WEB_DIR, UPLOAD_EXTENSIONS, [APP_DIR]);
    $sort = _sort_field($data['sort']);
    $uploads = _create_list($files);
    $uploads = list\filter($uploads, $data['filter']);
    $uploads = list\sort($uploads, $sort);
    $output = [$uploads, $data['filter'], $sort];

    return service\success($output);
}

/**
 * @param array{file: string} $input
 * @return array{0: string, 1: ?array}
 */
function delete(array $input): array
{
    $data = data\filter($input, [
        ['file', 'filename'],
    ]);

    $errors = data\validate($data, [
        ['file', 'required'],
        ['file', 'file_exists'],
        ['file', 'file_writeable'],
        ['file', 'html_file_name'],
        ['file', 'file_extension', 'allowedExtensions' => UPLOAD_EXTENSIONS]
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
 * @param file[] $files
 * @return upload[]
 */
function _create_list(array $files): array
{
    $list = [];
    foreach ($files as $file) {
        $list[] = new upload(
            $file->file,
            $file->extension,
            $file->size,
            $file->modified,
            null
        );
    }
    return $list;
}

/**
 * @return array{string, string}
 */
function _sort_field(string $sort): array
{
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

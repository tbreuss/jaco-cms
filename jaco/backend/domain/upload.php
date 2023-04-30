<?php

declare(strict_types=1);

namespace jaco\domain\upload;

use jaco\data;
use jaco\domain;
use jaco\filesystem;
use jaco\list;
use jaco\struct\file;
use jaco\struct\upload;

use const jaco\APP_DIR;
use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_EXTENSIONS;
use const jaco\WEB_DIR;

const SORT_PARAMETERS = ['file', 'extension', 'size', 'created'];

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
            ['sortedBy', 'sort_parameter', 'fields' => SORT_PARAMETERS],
        ]
    );

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    if (!filesystem\dir_exists(WEB_DIR)) {
        return domain\error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files_rec(WEB_DIR, UPLOAD_EXTENSIONS, [APP_DIR]);
    $sort = _sort_field($sortedBy);
    $uploads = _create_list($files);
    $uploads = list\filter($uploads, $filteredBy);
    $uploads = list\sort($uploads, $sort);
    $output = [$uploads, $filteredBy, $sort];

    return domain\success($output);
}

/**
 * @return array{string, array}
 */
function delete(string $file): array
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists'],
        ['file_writeable'],
        ['html_file_name'],
        ['file_extension', 'allowedExtensions' => UPLOAD_EXTENSIONS]
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
    if (!in_array(ltrim($sort, '-'), SORT_PARAMETERS)) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

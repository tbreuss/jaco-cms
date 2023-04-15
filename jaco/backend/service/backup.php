<?php

declare(strict_types=1);

namespace jaco\service\backup;

use jaco\data;
use jaco\filesystem;
use jaco\html;
use jaco\list;
use jaco\struct\backup;
use jaco\struct\file;

use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\UPLOAD_EXTENSIONS;
use const jaco\WEB_DIR;

const ERROR = 'ERROR';
const INVALID = 'INVALID';
const SUCCESS = 'SUCCESS';

/**
 * @phpstan-return array{0:backup[],1:string,2:array{string,string}}
 */
function find_all(string $filter = '', string $sort = '-created'): array
{
    $allowed = array_merge(UPLOAD_EXTENSIONS, PAGE_EXTENSIONS);
    $files = filesystem\find_files(BACKUP_DIR, $allowed);
    $backups = create_list($files, BACKUP_DIR, PAGE_EXTENSIONS);
    $backups = list\filter($backups, $filter);
    $sort = sort_field($sort);
    return [list\sort($backups, $sort), $filter, $sort];
}

function find_one(array $input): array
{
    $data = data\filter($input, [
        ['file', 'filename'],
    ]);

    $errors = data\validate2($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => BACKUP_DIR],
        ['file', 'file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS, UPLOAD_EXTENSIONS)],
        ['file', 'html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return [INVALID, $errors];
    }

    $file = $data['file'];

    $content = filesystem\read_file(BACKUP_DIR, $file);

    if ($content === null) {
        return [ERROR, [['file', 'Could not read file.']]];
    }

    $extension = \pathinfo($file, PATHINFO_EXTENSION);
    $type = in_array($extension, PAGE_EXTENSIONS) ? 'page' : 'upload';
    return [SUCCESS, (array)new backup(
        $file,
        str_replace('|', DIRECTORY_SEPARATOR, substr($file, 21)),
        html\get_title($content),
        filesystem\restore_timestamp_from_file_name($file),
        filesystem\file_mtime($file, BACKUP_DIR),
        $type,
        $type === 'page' ? $content : base64_encode($content)
    )];
}

/**
 * @param array{file: string} $input
 * @return array{0: string, 1: ?array}
 */
function restore(array $input): array
{
    $data = data\filter($input, [
        ['file', 'filename'],
    ]);

    $errors = data\validate2($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => BACKUP_DIR],
        ['file', 'file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS, UPLOAD_EXTENSIONS)],
        ['file', 'html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return [INVALID, $errors];
    }

    $status = filesystem\restore_file(BACKUP_DIR, $data['file'], WEB_DIR);

    if (!$status) {
        return [ERROR, [['file', 'Could not restore file.']]];
    }

    return [SUCCESS, null];
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

    $errors = data\validate2($data, [
        ['file', 'required'],
        ['file', 'file_exists', 'dir' => BACKUP_DIR],
    ]);

    if (count($errors) > 0) {
        return [INVALID, $errors];
    }

    $status = filesystem\delete_file(BACKUP_DIR, $input['file']);

    if (!$status) {
        return [ERROR, [['file', 'Could not delete file.']]];
    }

    return [SUCCESS, null];
}

/**
 * @param file[] $files
 * @param string[] $pageExtensions
 * @return backup[]
 */
function create_list(array $files, string $dir, array $pageExtensions): array
{
    $backups = [];
    foreach ($files as $file) {
        $content = filesystem\read_file($dir, $file->file);
        $backups[] = new backup(
            $file->file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file->file, 21)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file->file),
            $file->modified,
            in_array($file->extension, $pageExtensions) ? 'page' : 'upload'
        );
    }
    return $backups;
}

/**
 * @phpstan-return array{string,string}
 */
function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), ['backedUp', 'created', 'name', 'title', 'type'])) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

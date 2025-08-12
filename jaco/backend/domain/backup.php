<?php declare(strict_types=1);

namespace jaco\domain\backup;

use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;

use const jaco\BACKUP_DIR;
use const jaco\PAGE_EXTENSIONS;
use const jaco\UPLOAD_EXTENSIONS;
use const jaco\WEB_DIR;

const SORT_PARAMETERS = ['backedUp', 'created', 'name', 'title', 'type'];

final class Backup
{
    public function __construct(
        public string $file,
        public string $name,
        public string $title,
        public int $created,
        public int $backedUp,
        public string $type,
        public string $content = '',
    ) {}
}

final class DomainPayload extends \jaco\domain\Payload {}

function find_backups(string $filteredBy, string $sortedBy): DomainPayload
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

    if (!filesystem\dir_exists(BACKUP_DIR)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    $allowed = array_merge(UPLOAD_EXTENSIONS, PAGE_EXTENSIONS);
    $files = filesystem\find_files(BACKUP_DIR, $allowed);
    $backups = create_list($files, BACKUP_DIR, PAGE_EXTENSIONS);
    $backups = list\filter_list($backups, $filteredBy);
    $sort = sort_field($sortedBy);
    $output = [list\sort_list($backups, $sort), $filteredBy, $sort];

    return DomainPayload::success($output);
}

function find_backup(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => BACKUP_DIR],
        ['file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS, UPLOAD_EXTENSIONS)],
        ['html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $content = filesystem\read_file(BACKUP_DIR, $file);

    if ($content === null) {
        return DomainPayload::error([['file', 'Could not read file.']]);
    }

    $extension = \pathinfo($file, PATHINFO_EXTENSION);
    $type = in_array($extension, PAGE_EXTENSIONS) ? 'page' : 'upload';
    return DomainPayload::success(
        (array) new Backup(
            $file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file, 21)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file),
            filesystem\file_mtime($file, BACKUP_DIR),
            $type,
            $type === 'page' ? $content : base64_encode($content),
        ),
    );
}

function restore_backup(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => BACKUP_DIR],
        ['file_extension', 'allowedExtensions' => array_merge(PAGE_EXTENSIONS, UPLOAD_EXTENSIONS)],
        ['html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $status = filesystem\restore_file(BACKUP_DIR, $file, WEB_DIR);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not restore file.']]);
    }

    return DomainPayload::success();
}

function delete_backup(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => BACKUP_DIR],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $status = filesystem\delete_file(BACKUP_DIR, $file);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not delete file.']]);
    }

    return DomainPayload::success();
}

/**
 * @param filesystem\File[] $files
 * @param string[] $pageExtensions
 * @return Backup[]
 */
function create_list(array $files, string $dir, array $pageExtensions): array
{
    $backups = [];
    foreach ($files as $file) {
        $content = filesystem\read_file($dir, $file->file);
        $backups[] = new Backup(
            $file->file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file->file, 21)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file->file),
            $file->modified,
            in_array($file->extension, $pageExtensions) ? 'page' : 'upload',
        );
    }
    return $backups;
}

function sort_field(string $sort): array
{
    if (!in_array(ltrim($sort, '-'), SORT_PARAMETERS)) {
        return ['created', 'desc'];
    }
    $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $sortField = $sortDir === 'desc' ? substr($sort, 1) : $sort;
    return [$sortField, $sortDir];
}

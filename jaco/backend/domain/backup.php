<?php declare(strict_types=1);

namespace jaco\domain\backup;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;
use jaco\domain\Payload;

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


function find_backups(Config $config, string $filteredBy, string $sortedBy): Payload
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
        return Payload::invalid($errors);
    }

    if (!filesystem\dir_exists($config->backupDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files($config->backupDir, array_merge($config->uploadExtensions, $config->pageExtensions));

    // retention is lazy: every listing drops backups older than the
    // configured days, so no cron job is needed
    $files = prune_old_backups($config, $files);

    $sort = sort_field($sortedBy);
    $backups = create_list($files, $config->backupDir, $config->pageExtensions);
    $backups = list\filter_list($backups, $filteredBy);
    $backups = list\sort_list($backups, $sort);

    return Payload::success([
        'items' => $backups,
        'filter' => $filteredBy,
        'sort' => $sort,
    ]);
}

function find_backup(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->backupDir],
        ['file_extension', 'allowedExtensions' => array_merge($config->pageExtensions, $config->uploadExtensions)],
        ['html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $content = filesystem\read_file($config->backupDir, $file);

    if ($content === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    $extension = \pathinfo($file, PATHINFO_EXTENSION);
    $type = in_array($extension, $config->pageExtensions) ? 'page' : 'upload';
    return Payload::success(
        (array) new Backup(
            $file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file, filesystem\BACKUP_NAME_PREFIX_LENGTH)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file),
            filesystem\file_mtime($file, $config->backupDir),
            $type,
            $type === 'page' ? $content : base64_encode($content),
        ),
    );
}

function restore_backup(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->backupDir],
        ['file_extension', 'allowedExtensions' => array_merge($config->pageExtensions, $config->uploadExtensions)],
        ['html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $status = filesystem\restore_file($config, $config->backupDir, $file, $config->webDir);

    if (!$status) {
        return Payload::error([['file', 'Could not restore file.']]);
    }

    return Payload::success();
}

function delete_backup(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->backupDir],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $status = filesystem\delete_file($config->backupDir, $file);

    if (!$status) {
        return Payload::error([['file', 'Could not delete file.']]);
    }

    return Payload::success();
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
            str_replace('|', DIRECTORY_SEPARATOR, substr($file->file, filesystem\BACKUP_NAME_PREFIX_LENGTH)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file->file),
            $file->modified,
            in_array($file->extension, $pageExtensions) ? 'page' : 'upload',
        );
    }
    return $backups;
}

/**
 * @param filesystem\File[] $files
 * @return filesystem\File[] files within the retention period
 */
function prune_old_backups(Config $config, array $files): array
{
    $days = $config->backupRetentionDays;

    if ($days <= 0) {
        return $files;
    }

    $kept = [];
    foreach ($files as $file) {
        if ($file->modified < time() - $days * 86400) {
            filesystem\delete_file($config->backupDir, $file->file);
            continue;
        }
        $kept[] = $file;
    }

    return $kept;
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

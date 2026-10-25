<?php declare(strict_types=1);

namespace jaco\domain\backup;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;
use jaco\domain\Payload;

const SORT_PARAMETERS = ['backedUp', 'created', 'name', 'title', 'type'];

/**
 * Stylesheets are backed up by `css/update` as well, and their extension is
 * neither a page nor an upload. Without this type they counted as uploads, so a
 * rule such as "editors may restore pages and media, not stylesheets" could not
 * be expressed.
 */
const BACKUP_TYPE_PAGE = 'page';
const BACKUP_TYPE_UPLOAD = 'upload';
const BACKUP_TYPE_STYLESHEET = 'stylesheet';

/**
 * Extensions a backup can hold. Stylesheets are written by `css/update` and
 * backed up there, but `css` is in neither the page nor the upload extensions -
 * without it a stylesheet backup was written and never listed.
 *
 * @param string[] $uploadExtensions
 * @param string[] $pageExtensions
 * @return list<string>
 */
function get_backup_extensions(array $uploadExtensions, array $pageExtensions): array
{
    return \array_values(\array_merge($uploadExtensions, $pageExtensions, ['css']));
}

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
            ['filteredBy', 'is_string'],
            ['sortedBy', 'is_string'],
            ['sortedBy', 'is_sort_parameter', 'fields' => SORT_PARAMETERS],
        ],
    );

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    if (!filesystem\dir_exists($config->backupDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files($config->backupDir, get_backup_extensions($config->uploadExtensions, $config->pageExtensions));

    // retention is lazy: every listing drops backups older than the
    // configured days, so no cron job is needed
    $files = prune_old_backups($config, $files);

    $sort = list\parse_sort($sortedBy, SORT_PARAMETERS);
    $backups = create_list($files, $config->backupDir, $config->pageExtensions);
    $backups = list\filter_list($backups, $filteredBy);
    $backups = list\sort_list($backups, $sort);

    return Payload::success([
        'items' => $backups,
        'filter' => $filteredBy,
        'sort' => $sort,
    ]);
}

/**
 * Which kind of file a backup holds, taken from its extension: a page, a
 * stylesheet or an upload. Used for the listing, for the detail view and to
 * decide what a role may restore.
 *
 * @param string[] $pageExtensions
 */
function get_backup_type(string $extension, array $pageExtensions): string
{
    if (\in_array($extension, $pageExtensions, true)) {
        return BACKUP_TYPE_PAGE;
    }

    if ($extension === 'css') {
        return BACKUP_TYPE_STYLESHEET;
    }

    return BACKUP_TYPE_UPLOAD;
}

function find_backup(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->backupDir],
        ['has_file_extension', 'allowedExtensions' => get_backup_extensions($config->uploadExtensions, $config->pageExtensions)],
        ['has_html_file_name', 'isBackup' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $content = filesystem\read_file($config->backupDir, $file);

    if ($content === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    $extension = \pathinfo($file, PATHINFO_EXTENSION);
    $type = get_backup_type($extension, $config->pageExtensions);
    return Payload::success(
        (array) new Backup(
            $file,
            str_replace('|', DIRECTORY_SEPARATOR, substr($file, filesystem\BACKUP_NAME_PREFIX_LENGTH)),
            html\get_title($content),
            filesystem\restore_timestamp_from_file_name($file),
            filesystem\file_mtime($file, $config->backupDir),
            $type,
            $type === BACKUP_TYPE_PAGE ? $content : base64_encode($content),
        ),
    );
}

function restore_backup(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->backupDir],
        ['has_file_extension', 'allowedExtensions' => get_backup_extensions($config->uploadExtensions, $config->pageExtensions)],
        ['has_html_file_name', 'isBackup' => true],
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
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->backupDir],
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
            get_backup_type($file->extension, $pageExtensions),
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

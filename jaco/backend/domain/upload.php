<?php declare(strict_types=1);

namespace jaco\domain\upload;

use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\list;

use const jaco\APP_DIR;
use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_DIR;
use const jaco\UPLOAD_EXTENSIONS;
use const jaco\WEB_DIR;

const SORT_PARAMETERS = ['file', 'extension', 'size', 'created'];

final class Upload
{
    public function __construct(
        public string $file,
        public string $extension,
        public int $size,
        public int $created,
        public ?string $content,
    ) {}
}

final class DomainPayload extends \jaco\domain\Payload {}

function find_uploads(string $filteredBy, string $sortedBy): DomainPayload
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

    if (!filesystem\dir_exists(WEB_DIR)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files_rec(WEB_DIR, UPLOAD_EXTENSIONS, [APP_DIR]);

    $sort = sort_field($sortedBy);
    $uploads = create_list($files);
    $uploads = list\filter_list($uploads, $filteredBy);
    $uploads = list\sort_list($uploads, $sort);

    return DomainPayload::success([
        'items' => $uploads,
        'filter' => $filteredBy,
        'sort' => $sort,
    ]);
}

/**
 * Store an uploaded file in the upload directory. The argument is one
 * entry of the $_FILES array. Existing files are never overwritten,
 * a counter suffix is appended instead.
 *
 * @param array<string, mixed> $file
 */
function create_upload(array $file): DomainPayload
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error !== UPLOAD_ERR_OK) {
        return DomainPayload::invalid([['file', upload_error_message((int) $error)]]);
    }

    $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
    $size = isset($file['size']) && is_int($file['size']) ? $file['size'] : 0;

    if ($tmpName === '' || $size < 1) {
        return DomainPayload::invalid([['file', 'The file is empty.']]);
    }

    $name = data\sanitize_one((string) ($file['name'] ?? ''), [
        ['filename'],
    ]);
    $name = data\sanitize\normalize_filename($name);

    $errors = data\validate_one($name, 'file', [
        ['required'],
        ['file_extension', 'allowedExtensions' => UPLOAD_EXTENSIONS],
        ['html_file_name'],
    ]);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $newFile = filesystem\file_name_to_write(UPLOAD_DIR, $name);
    $moved = \is_uploaded_file($tmpName)
        ? \move_uploaded_file($tmpName, filesystem\combine(UPLOAD_DIR, $newFile))
        : \rename($tmpName, filesystem\combine(UPLOAD_DIR, $newFile));

    if (!$moved) {
        return DomainPayload::error([['file', 'Could not store file.']]);
    }

    return DomainPayload::success(['file' => $newFile]);
}

function upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file exceeds the size limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        default => 'The file could not be uploaded.',
    };
}

function delete_upload(string $file): DomainPayload
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists'],
        ['file_writeable'],
        ['html_file_name'],
        ['file_extension', 'allowedExtensions' => UPLOAD_EXTENSIONS],
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

/**
 * @param filesystem\File[] $files
 * @return Upload[]
 */
function create_list(array $files): array
{
    $list = [];
    foreach ($files as $file) {
        $list[] = new Upload(
            $file->file,
            $file->extension,
            $file->size,
            $file->modified,
            null,
        );
    }
    return $list;
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

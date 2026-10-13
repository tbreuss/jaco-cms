<?php declare(strict_types=1);

namespace jaco\domain\upload;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\list;
use jaco\domain\Payload;

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


function find_uploads(Config $config, string $filteredBy, string $sortedBy): Payload
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

    if (!filesystem\dir_exists($config->webDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files_rec($config->webDir, $config->uploadExtensions, [$config->appDir]);

    $sort = sort_field($sortedBy);
    $uploads = create_list($files);
    $uploads = list\filter_list($uploads, $filteredBy);
    $uploads = list\sort_list($uploads, $sort);

    return Payload::success([
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
function create_upload(Config $config, array $file): Payload
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error !== UPLOAD_ERR_OK) {
        return Payload::invalid([['file', get_upload_error_message((int) $error)]]);
    }

    $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
    $size = isset($file['size']) && is_int($file['size']) ? $file['size'] : 0;

    if ($tmpName === '' || $size < 1) {
        return Payload::invalid([['file', 'The file is empty.']]);
    }

    $name = data\sanitize_value((string) ($file['name'] ?? ''), [
        ['filename'],
    ]);
    $name = data\sanitize\normalize_filename($name);

    $errors = data\validate_value($name, 'file', [
        ['required'],
        ['file_extension', 'allowedExtensions' => $config->uploadExtensions],
        ['html_file_name'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $newFile = filesystem\file_name_to_write($config->uploadDir, $name);
    $moved = \is_uploaded_file($tmpName)
        ? \move_uploaded_file($tmpName, filesystem\combine($config->uploadDir, $newFile))
        : \rename($tmpName, filesystem\combine($config->uploadDir, $newFile));

    if (!$moved) {
        return Payload::error([['file', 'Could not store file.']]);
    }

    return Payload::success(['file' => $newFile]);
}

function get_upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file exceeds the size limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        default => 'The file could not be uploaded.',
    };
}

function delete_upload(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->webDir],
        ['file_writeable', 'dir' => $config->webDir],
        ['html_file_name'],
        ['file_extension', 'allowedExtensions' => $config->uploadExtensions],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $status = filesystem\backup_file($config->webDir, $file, $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file($config->webDir, $file);

    if (!$status) {
        return Payload::error([['file', 'Could not delete file.']]);
    }

    return Payload::success();
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

<?php declare(strict_types=1);

namespace jaco\domain\upload;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
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
            ['filteredBy', 'is_string'],
            ['sortedBy', 'is_string'],
            ['sortedBy', 'is_sort_parameter', 'fields' => SORT_PARAMETERS],
        ],
    );

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    if (!filesystem\dir_exists($config->webDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files_rec($config->webDir, $config->uploadExtensions, [$config->appDir]);

    $sort = list\parse_sort($sortedBy, SORT_PARAMETERS);
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
        ['to_filename'],
    ]);
    $name = data\sanitize\transliterate_filename($name);

    $errors = data\validate_value($name, 'file', [
        ['is_required'],
        ['has_file_extension', 'allowedExtensions' => $config->uploadExtensions],
        ['has_html_file_name'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $newFile = filesystem\file_name_to_write($config->mediaDir, $name);

    // svg uploads are scrubbed of active content on both write paths
    if (\strtolower(\pathinfo($name, PATHINFO_EXTENSION)) === 'svg') {
        $content = @\file_get_contents($tmpName);

        [$svgOk, $svgCleaned] = $content === false ? [false, ['Could not read file.']] : html\sanitize_svg($content);

        if (!$svgOk) {
            return Payload::invalid(\array_map(fn(string $error): array => ['file', $error], $svgCleaned));
        }

        if (@\file_put_contents(filesystem\combine($config->mediaDir, $newFile), $svgCleaned) === false) {
            return Payload::error([['file', 'Could not store file.']]);
        }

        return Payload::success(['file' => $newFile]);
    }

    $moved = \is_uploaded_file($tmpName)
        ? \move_uploaded_file($tmpName, filesystem\combine($config->mediaDir, $newFile))
        : \rename($tmpName, filesystem\combine($config->mediaDir, $newFile));

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
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->webDir],
        ['is_file_writable', 'dir' => $config->webDir],
        ['has_html_file_name'],
        ['has_file_extension', 'allowedExtensions' => $config->uploadExtensions],
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

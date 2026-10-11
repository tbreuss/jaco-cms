<?php declare(strict_types=1);

namespace jaco\domain\css;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\domain\Payload;

final class Stylesheet
{
    public function __construct(
        public string $file,
        public int $created,
    ) {}
}

function find_css_files(Config $config): Payload
{
    if (!filesystem\dir_exists($config->webDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    // backups and the inspiration snapshots are no site stylesheets
    $files = filesystem\find_files_rec($config->webDir, ['css'], [$config->appDir, $config->backupDir, 'inspiration']);

    return Payload::success(
        array_map(fn(filesystem\File $file): Stylesheet => new Stylesheet(
            file: $file->file,
            created: $file->modified,
        ), $files),
    );
}

/**
 * @return array{0: string, 1: array<array{string, string}>} validated file or errors
 */
function validated_file(Config $config, string $file): array
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => $config->webDir],
        ['file_extension', 'allowedExtensions' => ['css']],
        ['file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    return [$file, $errors];
}

function find_css(Config $config, string $file): Payload
{
    [$file, $errors] = validated_file($config, $file);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $css = filesystem\read_file($config->webDir, $file);

    if ($css === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    return Payload::success([
        'file' => $file,
        'created' => filesystem\file_mtime($file, $config->webDir),
        'content' => $css,
    ]);
}

function update_css(Config $config, string $file, string $withContent): Payload
{
    [$file, $errors] = validated_file($config, $file);

    $errors = array_merge($errors, data\validate_one($withContent, 'withContent', [
        ['required'],
        ['string'],
    ]));

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $status = filesystem\backup_file($config->webDir, $file, $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    // stylesheets are no HTML documents: sanitize_html would destroy them,
    // and there is no script vector in CSS served as text/css
    $status = filesystem\write_file($config, $config->webDir, $file, $withContent);

    if (!$status) {
        return Payload::error([['file', 'Could not write content to file.']]);
    }

    return Payload::success();
}

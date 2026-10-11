<?php declare(strict_types=1);

namespace jaco\domain\css;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;

final class DomainPayload extends \jaco\domain\Payload {}

final class Stylesheet
{
    public function __construct(
        public string $file,
        public int $created,
    ) {}
}

function find_css_files(Config $config): DomainPayload
{
    if (!filesystem\dir_exists($config->webDir)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    // backups and the inspiration snapshots are no site stylesheets
    $files = filesystem\find_files_rec($config->webDir, ['css'], [$config->appDir, $config->backupDir, 'inspiration']);

    return DomainPayload::success(
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

function find_css(Config $config, string $file): DomainPayload
{
    [$file, $errors] = validated_file($config, $file);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $css = filesystem\read_file($config->webDir, $file);

    if ($css === null) {
        return DomainPayload::error([['file', 'Could not read file.']]);
    }

    return DomainPayload::success([
        'file' => $file,
        'created' => filesystem\file_mtime($file, $config->webDir),
        'content' => $css,
    ]);
}

function update_css(Config $config, string $file, string $withContent): DomainPayload
{
    [$file, $errors] = validated_file($config, $file);

    $errors = array_merge($errors, data\validate_one($withContent, 'withContent', [
        ['required'],
        ['string'],
    ]));

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $status = filesystem\backup_file($config->webDir, $file, $config->backupDir);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    // stylesheets are no HTML documents: sanitize_html would destroy them,
    // and there is no script vector in CSS served as text/css
    $status = filesystem\write_file($config, $config->webDir, $file, $withContent);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not write content to file.']]);
    }

    return DomainPayload::success();
}

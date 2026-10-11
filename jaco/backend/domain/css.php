<?php declare(strict_types=1);

namespace jaco\domain\css;

use jaco\core\data;
use jaco\core\filesystem;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;

final class DomainPayload extends \jaco\domain\Payload {}

final class Stylesheet
{
    public function __construct(
        public string $file,
        public int $created,
    ) {}
}

function find_css_files(): DomainPayload
{
    if (!filesystem\dir_exists(WEB_DIR)) {
        return DomainPayload::error([['', 'Dir does not exist.']]);
    }

    // backups and the inspiration snapshots are no site stylesheets
    $files = filesystem\find_files_rec(WEB_DIR, ['css'], [APP_DIR, BACKUP_DIR, 'inspiration']);

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
function validated_file(string $file): array
{
    $file = data\sanitize_one($file, [
        ['filename'],
    ]);

    $errors = data\validate_one($file, 'file', [
        ['required'],
        ['file_exists', 'dir' => WEB_DIR],
        ['file_extension', 'allowedExtensions' => ['css']],
        ['file_in_dir', 'dir' => APP_DIR_NAME, 'negate' => true],
    ]);

    return [$file, $errors];
}

function find_css(string $file): DomainPayload
{
    [$file, $errors] = validated_file($file);

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $css = filesystem\read_file(WEB_DIR, $file);

    if ($css === null) {
        return DomainPayload::error([['file', 'Could not read file.']]);
    }

    return DomainPayload::success([
        'file' => $file,
        'created' => filesystem\file_mtime($file, WEB_DIR),
        'content' => $css,
    ]);
}

function update_css(string $file, string $withContent): DomainPayload
{
    [$file, $errors] = validated_file($file);

    $errors = array_merge($errors, data\validate_one($withContent, 'withContent', [
        ['required'],
        ['string'],
    ]));

    if (count($errors) > 0) {
        return DomainPayload::invalid($errors);
    }

    $status = filesystem\backup_file(WEB_DIR, $file, BACKUP_DIR);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not backup file.']]);
    }

    // stylesheets are no HTML documents: sanitize_html would destroy them,
    // and there is no script vector in CSS served as text/css
    $status = filesystem\write_file(WEB_DIR, $file, $withContent);

    if (!$status) {
        return DomainPayload::error([['file', 'Could not write content to file.']]);
    }

    return DomainPayload::success();
}

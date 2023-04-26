<?php

declare(strict_types=1);

namespace jaco\data\validator;

use jaco\filesystem;

use const jaco\WEB_DIR;

function numeric(mixed $value): array
{
    if (is_numeric($value)) {
        return [true, ''];
    }
    return [false, '{value} must be a number.'];
}

function string(mixed $value): array
{
    if (is_string($value)) {
        return [true, ''];
    }
    return [false, '{value} must be a string.'];
}

function required(mixed $value): array
{
    if (!is_null($value)) {
        return [true, ''];
    }
    return [false, '{value} cannot be blank.'];
}

function safe(): array
{
    return [true, ''];
}

/**
 * @param string[] $allowedExtensions
 * @return array{boolean, string}
 */
function file_extension(mixed $value, array $allowedExtensions = []): array
{
    if (is_string($value) && filesystem\validate_extension(WEB_DIR, $value, $allowedExtensions)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

function html_file_extension(mixed $value): array
{
    if (is_string($value) && filesystem\validate_extension(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

function html_file_name(mixed $value, bool $isBackup = false): array
{
    if (is_string($value) && filesystem\validate_filename(WEB_DIR, $value, $isBackup)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid filename'];
}

function file_writeable(mixed $value): array
{
    if (is_string($value) && filesystem\file_is_writable(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is not writeable.'];
}

function file_exists(mixed $value, string $dir = WEB_DIR, bool $negate = false): array
{
    if (is_string($value) && (filesystem\file_exists($dir, $value) === !$negate)) {
        return [true, ''];
    }
    return [false, $negate === false ? '{value} does not exist.' : '{value} must not exist.'];
}

function file_in_dir(mixed $value, string $dir, bool $negate = false): array
{
    if (!$negate && !filesystem\file_in_dir($value, $dir)) {
        return [false, sprintf('{value} must be in the %s folder.', $dir)];
    }

    if ($negate && filesystem\file_in_dir($value, $dir)) {
        return [false, sprintf('{value} must not be in the %s folder.', $dir)];
    }

    return [true, ''];
}

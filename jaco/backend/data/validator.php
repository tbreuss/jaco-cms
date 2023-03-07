<?php

declare(strict_types=1);

namespace jaco\data\validator;

use jaco\filesystem;

use const jaco\APP_DIR_NAME;
use const jaco\WEB_DIR;

/**
 * @phpstan-return array{bool, string}
 */
function numeric(mixed $value): array
{
    if (is_numeric($value)) {
        return [true, ''];
    }
    return [false, '{value} must be a number.'];
}

/**
 * @phpstan-return array{bool, string}
 */
function string(mixed $value): array
{
    if (is_string($value)) {
        return [true, ''];
    }
    return [false, '{value} must be a string.'];
}

/**
 * @phpstan-return array{bool, string}
 */
function required(mixed $value): array
{
    if (is_string($value) && trim($value) !== '') {
        return [true, ''];
    }
    return [false, '{value} cannot be blank.'];
}

/**
 * @phpstan-return array{bool, string}
 */
function safe(): array
{
    return [true, ''];
}

/**
 * @phpstan-return array{bool, string}
 */
function test(mixed $value, int $avg, int $min, int $max, int $sum): array
{
    return [true, ''];
}

/**
 * @param string[] $allowed
 * @return array{boolean, string}
 */
function file_extension(mixed $value, array $allowed = []): array
{
    if (is_string($value) && filesystem\validate_extension(WEB_DIR, $value, $allowed)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

/**
 * @phpstan-return array{bool, string}
 * @deprecated
 */
function htmlfile_extension(mixed $value): array
{
    if (is_string($value) && filesystem\validate_extension(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

/**
 * @phpstan-return array{bool, string}
 */
function htmlfile_name(mixed $value, bool $isBackup = false): array
{
    if (is_string($value) && filesystem\validate_filename(WEB_DIR, $value, $isBackup)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid filename'];
}

/**
 * @phpstan-return array{bool, string}
 */
function file_writeable(mixed $value): array
{
    if (is_string($value) && filesystem\file_is_writable(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is not writeable.'];
}

/**
 * @phpstan-return array{bool, string}
 */
function file_exists(mixed $value, string $dir = WEB_DIR): array
{
    if (is_string($value) && filesystem\file_exists($dir, $value)) {
        return [true, ''];
    }
    return [false, '{value} does not exist.'];
}

/**
 * @phpstan-return array{bool, string}
 */
function file_not_exists(mixed $value): array
{
    if (!filesystem\file_exists(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} must not exist.'];
}

/**
 * @phpstan-return array{bool, string}
 */
function file_not_in_jaco_dir(mixed $value): array
{
    $parts = explode(DIRECTORY_SEPARATOR, $value);
    $first = $parts[0] ?? '';

    if ($first === APP_DIR_NAME) {
        return [false, sprintf('{value} must not be in the protected %s folder.', APP_DIR_NAME)];
    }

    return [true, ''];
}

<?php

declare(strict_types=1);

namespace jaco\data\validate;

use jaco\filesystem;

use const jaco\WEB_DIR;

function numeric(mixed $value): array
{
    if (!is_numeric($value)) {
        return [false, '{value} must be a number.'];
    }
    return [true, ''];
}

function string(mixed $value, ?int $min = null, ?int $max = null): array
{
    if (!is_string($value)) {
        return [false, '{value} must be a string.'];
    }
    if ($min && (strlen($value) < abs($min))) {
        return [false, "{value} is too short, minimum is $min characters."];
    }
    if ($max && (strlen($value) > abs($max))) {
        return [false, "{value} is too long, maximum is $max characters."];
    }
    return [true, ''];
}

function required(array|string|float|int|bool|null $value): array
{
    $emptyMsg = '{value} cannot be blank.';

    if (is_null($value)) {
        return [false, $emptyMsg];
    }

    if (is_array($value) && (count($value) === 0)) {
        return [false, $emptyMsg];
    }

    if (is_string($value) && (strlen($value) === 0)) {
        return [false, $emptyMsg];
    }

    return [true, ''];
}

function safe(): array
{
    return [true, ''];
}

/**
 * @param string[] $allowedExtensions
 * @return array{boolean, string}
 */
function file_extension(string $value, array $allowedExtensions = []): array
{
    if (filesystem\validate_extension(WEB_DIR, $value, $allowedExtensions)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

function html_file_extension(string $value): array
{
    if (filesystem\validate_extension(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

function html_file_name(string $value, bool $isBackup = false): array
{
    if (filesystem\validate_filename(WEB_DIR, $value, $isBackup)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid filename'];
}

function file_writeable(string $value): array
{
    if (filesystem\file_is_writable(WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is not writeable.'];
}

function file_exists(string $value, string $dir = WEB_DIR, bool $negate = false): array
{
    if (filesystem\file_exists($dir, $value) === !$negate) {
        return [true, ''];
    }
    return [false, $negate === false ? '{value} does not exist.' : '{value} must not exist.'];
}

function file_in_dir(string $value, string $dir, bool $negate = false): array
{
    if (!$negate && !filesystem\file_in_dir($value, $dir)) {
        return [false, sprintf('{value} must be in the %s folder.', $dir)];
    }

    if ($negate && filesystem\file_in_dir($value, $dir)) {
        return [false, sprintf('{value} must not be in the %s folder.', $dir)];
    }

    return [true, ''];
}

function sort_parameter(string $value, array $fields = []): array
{
    $field = ltrim($value, '-');
    if (!in_array($field, $fields)) {
        return [false, '{value} is a invalid sort parameter.'];
    }

    return [true, ''];
}

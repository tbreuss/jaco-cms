<?php declare(strict_types=1);

namespace jaco\core\data\validate;

use jaco\core\filesystem;

final class Result
{
    public function __construct(
        public bool $isValid,
        public string $message = '',
    ) {}

    public static function ok(): self
    {
        return new self(true);
    }

    public static function error(string $message): self
    {
        return new self(false, $message);
    }
}

function numeric(mixed $value): Result
{
    if (!is_numeric($value)) {
        return Result::error('{value} must be a number.');
    }
    return Result::ok();
}

function string(mixed $value, ?int $min = null, ?int $max = null): Result
{
    if (!is_string($value)) {
        return Result::error('{value} must be a string.');
    }
    if ($min && (strlen($value) < abs($min))) {
        return Result::error("{value} is too short, minimum is $min characters.");
    }
    if ($max && (strlen($value) > abs($max))) {
        return Result::error("{value} is too long, maximum is $max characters.");
    }
    return Result::ok();
}

function required(array|string|float|int|bool|null $value): Result
{
    $emptyMsg = '{value} cannot be blank.';

    if (is_null($value)) {
        return Result::error($emptyMsg);
    }

    if (is_array($value) && (count($value) === 0)) {
        return Result::error($emptyMsg);
    }

    if (is_string($value) && (strlen($value) === 0)) {
        return Result::error($emptyMsg);
    }

    return Result::ok();
}

function safe(mixed $value = null): Result
{
    return Result::ok();
}

/**
 * @param string[] $allowedExtensions
 */
function file_extension(string $value, array $allowedExtensions = []): Result
{
    if (filesystem\validate_extension($value, $allowedExtensions)) {
        return Result::ok();
    }
    return Result::error('{value} is an invalid extension');
}

function html_file_extension(string $value, array $allowedExtensions): Result
{
    if (filesystem\validate_extension($value, $allowedExtensions)) {
        return Result::ok();
    }
    return Result::error('{value} is an invalid extension');
}

function html_file_name(string $value, bool $isBackup = false): Result
{
    if (str_contains($value, '..')) {
        return Result::error('{value} is an invalid filename');
    }
    if (filesystem\validate_filename($value, $isBackup)) {
        return Result::ok();
    }
    return Result::error('{value} is an invalid filename');
}

function file_writeable(string $value, string $dir): Result
{
    if (filesystem\file_is_writable($dir, $value)) {
        return Result::ok();
    }
    return Result::error('{value} is not writeable.');
}

function file_exists(string $value, string $dir, bool $negate = false): Result
{
    if (str_contains($value, '..')) {
        return Result::error($negate === false ? '{value} does not exist.' : '{value} must not exist.');
    }
    if (filesystem\file_exists($dir, $value) === !$negate) {
        return Result::ok();
    }
    return Result::error($negate === false ? '{value} does not exist.' : '{value} must not exist.');
}

function file_in_dir(string $value, string $dir, bool $negate = false): Result
{
    if (!$negate && !filesystem\file_in_dir($value, $dir)) {
        return Result::error(sprintf('{value} must be in the %s folder.', $dir));
    }

    if ($negate && filesystem\file_in_dir($value, $dir)) {
        return Result::error(sprintf('{value} must not be in the %s folder.', $dir));
    }

    return Result::ok();
}

function sort_parameter(string $value, array $fields = []): Result
{
    $field = ltrim($value, '-');
    if (!in_array($field, $fields)) {
        return Result::error('{value} is a invalid sort parameter.');
    }

    return Result::ok();
}

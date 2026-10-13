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

function is_numeric(mixed $value): Result
{
    if (!\is_numeric($value)) {
        return Result::error('{value} must be a number.');
    }
    return Result::ok();
}

function is_string(mixed $value, ?int $min = null, ?int $max = null): Result
{
    if (!\is_string($value)) {
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

function is_required(array|string|float|int|bool|null $value): Result
{
    $emptyMsg = '{value} cannot be blank.';

    if (is_null($value)) {
        return Result::error($emptyMsg);
    }

    if (is_array($value) && (count($value) === 0)) {
        return Result::error($emptyMsg);
    }

    if (\is_string($value) && (strlen($value) === 0)) {
        return Result::error($emptyMsg);
    }

    return Result::ok();
}

// the value is intentionally unused: "safe" is the explicit opt-out of
// validation, but every rule is called with the value by the dispatcher
// @phpstan-ignore function.unusedParameter
function is_safe(mixed $value = null): Result
{
    return Result::ok();
}

/**
 * @param string[] $allowedExtensions
 */
function has_file_extension(string $value, array $allowedExtensions): Result
{
    if (filesystem\validate_extension($value, $allowedExtensions)) {
        return Result::ok();
    }
    return Result::error('{value} is an invalid extension');
}

function has_html_file_name(string $value, bool $isBackup = false): Result
{
    if ($error = _traversal_error($value, '{value} is an invalid filename')) {
        return $error;
    }
    if (filesystem\validate_filename($value, $isBackup)) {
        return Result::ok();
    }
    return Result::error('{value} is an invalid filename');
}

function is_file_writable(string $value, string $dir): Result
{
    if (filesystem\is_file_writable($dir, $value)) {
        return Result::ok();
    }
    return Result::error('{value} is not writeable.');
}

function is_file_present(string $value, string $dir, bool $negate = false): Result
{
    $message = $negate === false ? '{value} does not exist.' : '{value} must not exist.';

    if ($error = _traversal_error($value, $message)) {
        return $error;
    }
    if (filesystem\file_exists($dir, $value) === !$negate) {
        return Result::ok();
    }
    return Result::error($message);
}

function is_file_in_dir(string $value, string $dir, bool $negate = false): Result
{
    if (!$negate && !filesystem\is_file_in_dir($value, $dir)) {
        return Result::error(str_replace('{dir}', $dir, '{value} must be in the {dir} folder.'));
    }

    if ($negate && filesystem\is_file_in_dir($value, $dir)) {
        return Result::error(str_replace('{dir}', $dir, '{value} must not be in the {dir} folder.'));
    }

    return Result::ok();
}

function is_sort_parameter(string $value, array $fields = []): Result
{
    $field = ltrim($value, '-');
    if (!in_array($field, $fields)) {
        return Result::error('{value} is an invalid sort parameter.');
    }

    return Result::ok();
}

// Traversal sequences are never valid inside a file name. The helper
// returns the error to report, or null when the value may pass on.
function _traversal_error(string $value, string $message): ?Result
{
    return str_contains($value, '..') ? Result::error($message) : null;
}

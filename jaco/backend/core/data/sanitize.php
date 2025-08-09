<?php

declare(strict_types=1);

namespace jaco\core\data\sanitize;

function integer(mixed $value): int
{
    if (!is_scalar($value)) {
        return 0;
    }
    return (int)$value;
}

function float(mixed $value): float
{
    if (!is_scalar($value)) {
        return 0.0;
    }
    return (float)$value;
}

function string(mixed $value): string
{
    if (!is_scalar($value)) {
        return '';
    }
    return (string)$value;
}

function html_specialchars(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }
    return \htmlspecialchars($value);
}

function filename(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }

    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $value);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    $absolutes = [];
    foreach ($parts as $part) {
        $part = trim($part, '.');
        if ($part === '') {
            continue;
        }
        $absolutes[] = $part;
    }
    return implode(DIRECTORY_SEPARATOR, $absolutes);
}

function safe(mixed $value): mixed
{
    return $value;
}

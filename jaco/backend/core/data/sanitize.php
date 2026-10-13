<?php declare(strict_types=1);

namespace jaco\core\data\sanitize;

function to_integer(mixed $value): int
{
    if (!is_scalar($value)) {
        return 0;
    }
    return (int) $value;
}

function to_float(mixed $value): float
{
    if (!is_scalar($value)) {
        return 0.0;
    }
    return (float) $value;
}

function to_string(mixed $value): string
{
    if (!is_scalar($value)) {
        return '';
    }
    return (string) $value;
}

function escape_html(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }
    return \htmlspecialchars($value);
}

function to_filename(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }

    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $value);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), function ($value) {
        return strlen($value) > 0;
    });

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

function transliterate_filename(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }

    $value = strtr($value, [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
        'ý' => 'y', 'ÿ' => 'y', 'Ý' => 'Y',
        'ç' => 'c', 'Ç' => 'C', 'ñ' => 'n', 'Ñ' => 'N',
    ]);

    // split the extension first, so dots need no special treatment below
    $extension = '';
    if (($pos = strrpos($value, '.')) !== false) {
        $extension = (string) preg_replace('/[^A-Za-z0-9]+/', '', substr($value, $pos + 1));
        $value = substr($value, 0, $pos);
    }

    // separators survive for the traversal check, every other run of
    // unsafe characters becomes a single dash
    $value = (string) preg_replace('/[^A-Za-z0-9\\/]+/', '-', $value);
    $value = trim($value, '-');

    return $extension === '' ? $value : $value . '.' . $extension;
}

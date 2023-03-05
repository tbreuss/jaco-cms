<?php

declare(strict_types=1);

namespace jaco\filesystem\shared;

/**
 * @deprecated
 */
function rand_string(int $length = 6): string
{
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

/**
 * @deprecated
 */
function join(string $dir, string $file): string
{
    $dir = \rtrim($dir, DIRECTORY_SEPARATOR);
    $file = \ltrim($file, DIRECTORY_SEPARATOR);
    return $dir . DIRECTORY_SEPARATOR . $file;
}

/**
 * @deprecated
 */
function normalize(string $dir): string
{
    $normalized = realpath($dir);
    if ($normalized === false) {
        trigger_error('Path ' . $dir . ' is not valid', E_USER_ERROR);
    }
    return $normalized;
}

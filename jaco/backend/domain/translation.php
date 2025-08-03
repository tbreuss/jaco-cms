<?php

declare(strict_types=1);

namespace jaco\domain\translation;

use jaco\lib\payload;

use const jaco\DEFAULT_LANGUAGE;

/**
 * @return array{string, array}
 */
function load_all(string $forLanguage): array
{
    $translations = _load($forLanguage);

    return payload\success($translations);
}

/**
 * @return array{string, array}
 */
function load_one(string $forLanguage, string $withKey): array
{
    $translations = _load($forLanguage);

    return payload\success($translations[$withKey] ?? $withKey);
}

function _load(string $language): array
{
    static $translations = [];

    if (isset($translations[$language])) {
        return $translations[$language];
    }

    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);

    if (is_file($path)) {
        $translations[$language] = json_decode(file_get_contents($path), true);
        return $translations[$language];
    }

    return _load(DEFAULT_LANGUAGE);
}

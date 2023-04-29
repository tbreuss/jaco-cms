<?php

declare(strict_types=1);

namespace jaco\domain\translation;

use jaco\domain;

use const jaco\DEFAULT_LANGUAGE;

function load_all(string $forLanguage): array
{
    $translations = _load($forLanguage);

    return domain\success($translations);
}

function load_one(string $forLanguage, string $withKey): string
{
    $translations = _load($forLanguage);

    return $translations[$withKey] ?? $withKey;
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

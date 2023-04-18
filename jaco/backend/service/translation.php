<?php

declare(strict_types=1);

namespace jaco\service\translation;

use jaco\service;

function all(string $language): array
{
    return _load($language);
}

function one(string $language, string $key): string
{
    $translations = _load($language);
    return $translations[$key] ?? $key;
}

function _load(string $language): ?array
{
    static $translations = [];
    if (!empty($translations[$language])) {
        return $translations[$language];
    }

    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);
    if (is_file($path)) {
        $translations[$language] = json_decode(file_get_contents($path), true);
        return service\success($translations[$language]);
    }

    return service\error([['language', 'Could not load language file.']]);
}

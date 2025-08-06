<?php

declare(strict_types=1);

namespace jaco\domain\translation;

use const jaco\DEFAULT_LANGUAGE;

class TranslationPayload extends \jaco\domain\Payload
{}

function load_all(string $forLanguage): TranslationPayload
{
    $translations = _load($forLanguage);

    return TranslationPayload::success($translations);
}

function load_one(string $forLanguage, string $withKey): TranslationPayload
{
    $translations = _load($forLanguage);

    return TranslationPayload::success($translations[$withKey] ?? $withKey);
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

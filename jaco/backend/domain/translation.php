<?php declare(strict_types=1);

namespace jaco\domain\translation;

use const jaco\DEFAULT_LANGUAGE;

final class DomainPayload extends \jaco\domain\Payload {}

function load_translations(string $forLanguage): DomainPayload
{
    $translations = load($forLanguage);

    return DomainPayload::success($translations);
}

function load_translation(string $forLanguage, string $withKey): DomainPayload
{
    $translations = load($forLanguage);

    return DomainPayload::success($translations[$withKey] ?? $withKey);
}

function load(string $language): array
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

    return load(DEFAULT_LANGUAGE);
}

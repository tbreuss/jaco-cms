<?php declare(strict_types=1);

namespace jaco\domain\translation;

use jaco\core\config\Config;
use jaco\domain\Payload;

function load_translations(Config $config, string $forLanguage): Payload
{
    $translations = load($config, $forLanguage);

    return Payload::success($translations);
}

function load_translation(Config $config, string $forLanguage, string $withKey): Payload
{
    $translations = load($config, $forLanguage);

    return Payload::success($translations[$withKey] ?? $withKey);
}

function load(Config $config, string $language): array
{
    static $translations = [];

    if (isset($translations[$language])) {
        return $translations[$language];
    }

    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);

    if (is_file($path)) {
        $decoded = json_decode(file_get_contents($path), true);
        $translations[$language] = is_array($decoded) ? $decoded : [];
    } elseif ($language === $config->defaultLanguage) {
        // the default language file is missing, return empty translations
        $translations[$language] = [];
    } else {
        $translations[$language] = load($config, $config->defaultLanguage);
    }

    return $translations[$language];
}

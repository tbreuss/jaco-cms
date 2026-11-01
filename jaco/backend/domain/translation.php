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

/**
 * Codes of the translation files that actually exist, sorted. Only plain two
 * letter file names count, so a stray file in the folder cannot become a choice.
 *
 * @return list<string>
 */
function find_language_codes(): array
{
    $codes = [];

    foreach (\glob(language_dir() . '/*.json') ?: [] as $file) {
        $code = \basename($file, '.json');

        if (\preg_match('/^[a-z]{2}$/', $code) === 1) {
            $codes[] = $code;
        }
    }

    \sort($codes);

    return $codes;
}

function language_dir(): string
{
    return \dirname(__DIR__, 2) . '/language';
}

function load(Config $config, string $language): array
{
    static $translations = [];

    // the same language code resolves differently depending on the configured
    // default language, so both have to be part of the cache key
    $key = $config->defaultLanguage . '|' . $language;

    if (isset($translations[$key])) {
        return $translations[$key];
    }

    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);

    // the language comes from user input, so only plain language codes
    // may reach the file system, everything else falls back to default
    if (!preg_match('/^[a-z]{2}$/i', $language)) {
        $path = '';
    }

    if ($path !== '' && is_file($path)) {
        $content = file_get_contents($path);
        $decoded = $content === false ? null : json_decode($content, true);
        $translations[$key] = is_array($decoded) ? $decoded : [];
    } elseif ($language === $config->defaultLanguage) {
        // the default language file is missing, return empty translations
        $translations[$key] = [];
    } else {
        $translations[$key] = load($config, $config->defaultLanguage);
    }

    return $translations[$key];
}

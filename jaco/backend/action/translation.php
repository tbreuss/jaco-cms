<?php

declare(strict_types=1);

namespace jaco\action\translation;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $language = JACO_DEFAULT_LANGUAGE): array
{
    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);
    if (is_file($path)) {
        return [200, json_decode(file_get_contents($path), true)];
    }
    return main(JACO_DEFAULT_LANGUAGE);
}

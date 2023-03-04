<?php

declare(strict_types=1);

namespace jaco\action\translation;

use jaco\response;

use const jaco\DEFAULT_LANGUAGE;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $language = DEFAULT_LANGUAGE): array
{
    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);
    if (is_file($path)) {
        return [response\OK, json_decode(file_get_contents($path), true)];
    }
    return main(DEFAULT_LANGUAGE);
}

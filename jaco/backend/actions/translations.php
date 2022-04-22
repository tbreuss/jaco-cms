<?php

namespace jaco\actions\translations;

function main(string $language = JACO_DEFAULT_LANGUAGE): array
{
    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);
    if (is_file($path)) {
        return json_decode(file_get_contents($path), true);
    }
    return main(JACO_DEFAULT_LANGUAGE);
}

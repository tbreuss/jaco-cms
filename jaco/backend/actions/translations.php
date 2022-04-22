<?php

namespace jaco\actions\translations;

function main(string $lang = JACO_DEFAULT_LANGUAGE): array
{
    $template = dirname(__DIR__, 2) . '/lang/{lang}.json';
    $path = str_replace('{lang}', $lang, $template);
    if (is_file($path)) {
        return json_decode(file_get_contents($path), true);
    }
    return main(JACO_DEFAULT_LANGUAGE);
}

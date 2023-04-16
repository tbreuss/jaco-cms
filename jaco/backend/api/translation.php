<?php

declare(strict_types=1);

namespace jaco\api\translation;

use jaco\attribute;
use jaco\http\response;

use const jaco\DEFAULT_LANGUAGE;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/translation/response.json')]
function action(string $language = DEFAULT_LANGUAGE): array
{
    $template = dirname(__DIR__, 2) . '/language/{language}.json';
    $path = str_replace('{language}', $language, $template);
    if (is_file($path)) {
        return response\ok(json_decode(file_get_contents($path), true));
    }
    return action(DEFAULT_LANGUAGE);
}

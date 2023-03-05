<?php

declare(strict_types=1);

namespace jaco\action\index;

use jaco\response;

use const jaco\APP_VERSION;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $version = APP_VERSION): array
{
    return [response\OK, [
        'name' => 'jaco',
        'version' => $version
    ]];
}

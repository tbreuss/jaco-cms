<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\response;

use const jaco\APP_VERSION;

/**
 * @phpstan-return array<int,mixed>
 */
function action(string $version = APP_VERSION): array
{
    return [response\OK, [
        'name' => 'jaco',
        'version' => $version
    ]];
}

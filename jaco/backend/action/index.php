<?php

declare(strict_types=1);

namespace jaco\action\index;

use const jaco\VERSION;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $version = VERSION): array
{
    return [200, [
        'name' => 'jaco',
        'version' => $version
    ]];
}

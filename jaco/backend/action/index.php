<?php

declare(strict_types=1);

namespace jaco\action\index;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $version = JACO_VERSION): array
{
    return [200, [
        'name' => 'jaco',
        'version' => $version
    ]];
}

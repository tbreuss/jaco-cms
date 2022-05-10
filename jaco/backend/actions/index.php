<?php

declare(strict_types=1);

namespace jaco\actions\index;

/**
 * @return array<string, string>
 */
function main(string $version = JACO_VERSION): array
{
    return [
        'name' => 'jaco',
        'version' => $version
    ];
}

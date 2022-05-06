<?php

declare(strict_types=1);

namespace jaco\actions\index;

function main(string $version = JACO_VERSION): array
{
    return [
        'name' => 'jaco',
        'version' => $version
    ];
}

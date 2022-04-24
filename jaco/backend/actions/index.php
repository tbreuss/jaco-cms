<?php

namespace jaco\actions\index;

function main(string $version = JACO_VERSION): array
{
    return [
        'name' => 'jaco',
        'version' => $version
    ];
}

<?php

namespace jaco\actions\page\delete;

use jaco\request;

function main(): array
{
    $data = request\post_values();
    $fileName = $data['fileName'] ?? '';
    return [$fileName];
}

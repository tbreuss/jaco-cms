<?php

declare(strict_types=1);

namespace jaco\actions\template\pages;

use jaco\models\template\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $pages = page\find_all();
    return [200, $pages];
}

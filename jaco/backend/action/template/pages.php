<?php

declare(strict_types=1);

namespace jaco\action\template\pages;

use jaco\model\template\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $pages = page\find_all();
    return [200, $pages];
}

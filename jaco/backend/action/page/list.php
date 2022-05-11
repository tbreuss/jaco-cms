<?php

declare(strict_types=1);

namespace jaco\action\page\list;

use jaco\service\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $pages = page\find_all_rec();
    return [200, $pages];
}

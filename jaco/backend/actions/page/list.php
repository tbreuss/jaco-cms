<?php

declare(strict_types=1);

namespace jaco\actions\page\list;

use jaco\models\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $pages = page\find_all_rec();
    return [200, $pages];
}

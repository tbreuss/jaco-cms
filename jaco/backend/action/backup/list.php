<?php

declare(strict_types=1);

namespace jaco\action\backup\list;

use jaco\model\backup;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $filter = '', string $sort = '-created'): array
{
    $pages = backup\find_all($filter, $sort);
    return [200, $pages];
}

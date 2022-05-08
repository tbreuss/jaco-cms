<?php

declare(strict_types=1);

namespace jaco\actions\backup\list;

use jaco\models\backup;

function main(string $filter = '', string $sort = '-created'): array
{
    $pages = backup\find_all($filter, $sort);
    return $pages;
}

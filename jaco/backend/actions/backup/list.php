<?php

declare(strict_types=1);

namespace jaco\actions\backup\list;

use jaco\models\backup;

function main(): array
{
    $pages = backup\find_all();
    return $pages;
}

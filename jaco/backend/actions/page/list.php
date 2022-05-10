<?php

declare(strict_types=1);

namespace jaco\actions\page\list;

use jaco\models\page;

/**
 * @return \jaco\structs\page[]
 */
function main(): array
{
    $pages = page\find_all_rec();
    return $pages;
}

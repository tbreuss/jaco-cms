<?php

declare(strict_types=1);

namespace jaco\actions\template\pages;

use jaco\models\template\page;

/**
 * @return \jaco\structs\page[]
 */
function main(): array
{
    $pages = page\find_all();
    return $pages;
}

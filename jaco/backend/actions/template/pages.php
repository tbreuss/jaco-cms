<?php

declare(strict_types=1);

namespace jaco\actions\template\pages;

use jaco\models\template\page;

function main(): array
{
    $files = page\find_all();
    return $files;
}

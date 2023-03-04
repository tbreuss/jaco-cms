<?php

declare(strict_types=1);

namespace jaco\action\template\pages;

use jaco\response;
use jaco\service\template\page;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $pages = page\find_all();
    return [response\OK, $pages];
}

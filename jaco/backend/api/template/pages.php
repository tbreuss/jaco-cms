<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\response;
use jaco\service\template\page;

/**
 * @phpstan-return array<int,mixed>
 */
function action(): array
{
    $pages = page\find_all();
    return response\ok($pages);
}

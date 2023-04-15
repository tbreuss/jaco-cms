<?php

declare(strict_types=1);

namespace jaco\api\template\pages;

use jaco\response;
use jaco\service\template\page;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\response('/api/template/response.json')]
function action(): array
{
    $pages = page\find_all();
    return response\ok($pages);
}

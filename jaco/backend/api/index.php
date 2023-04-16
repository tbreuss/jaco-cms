<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\attribute;
use jaco\http\response;

use const jaco\APP_VERSION;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/index/response.json')]
function action(string $version = APP_VERSION): array
{
    return response\ok([
        'name' => 'jaco',
        'version' => $version
    ]);
}

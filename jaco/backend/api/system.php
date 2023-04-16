<?php

declare(strict_types=1);

namespace jaco\api\system;

use jaco\attribute;
use jaco\http\response;
use jaco\system;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/system/response.json')]
function action(): array
{
    return response\ok([
        'classes' => system\defined_classes(),
        'constants' => system\defined_constants(),
        'functions' => system\defined_functions(),
    ]);
}

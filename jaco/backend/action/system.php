<?php

declare(strict_types=1);

namespace jaco\action\system;

use jaco\response;
use jaco\system;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    return [response\OK, [
        'classes' => system\defined_classes(),
        'constants' => (array)system\defined_constants(),
        'functions' => system\defined_functions()
    ]];
}

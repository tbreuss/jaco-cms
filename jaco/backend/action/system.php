<?php

declare(strict_types=1);

namespace jaco\action\system;

use jaco\system;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    return [200, [
        'classes' => system\defined_classes(),
        'constants' => (array)system\defined_constants(),
        'functions' => system\defined_functions()
    ]];
}

<?php

declare(strict_types=1);

namespace jaco\actions\system;

use jaco\system;

function main(): array
{
    return [
        'constants' => (array)system\defined_constants(),
        'functions' => system\defined_functions()
    ];
}

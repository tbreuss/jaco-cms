<?php

declare(strict_types=1);

namespace jaco\action\backup\view;

use jaco\service\backup;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $file): array
{
    return [200, (array)backup\find_one($file)];
}

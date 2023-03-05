<?php

declare(strict_types=1);

namespace jaco\action\backup\view;

use jaco\response;

use jaco\service\backup;

/**
 * @phpstan-return array<int,mixed>
 */
function main(string $file): array
{
    $backup = backup\find_one($file);

    if ($backup) {
        return [response\OK, (array)$backup];
    }

    return [response\NOT_FOUND];
}

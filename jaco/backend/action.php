<?php

declare(strict_types=1);

namespace jaco\action;

const OK = 1;
const VALIDATION_ERROR = 1;
const CORE_ERROR = 2;

/**
 * @phpstan-return array{int}
 */
function ok(int $code = OK): array
{
    return [$code];
}

/**
 * @phpstan-param array<string, string> $errors
 * @phpstan-return array{int, array<string, string>}
 */
function err(int $code, array $errors): array
{
    return [$code, $errors];
}

<?php

declare(strict_types=1);

namespace jaco\lib\payload;

const ERROR = 'ERROR';
const INVALID = 'INVALID';
const SUCCESS = 'SUCCESS';

function error(array $errors): array
{
    return [ERROR, $errors];
}

function invalid(array $errors): array
{
    return [INVALID, $errors];
}

function success(array|string|null $data = null): array
{
    return [SUCCESS, $data];
}

<?php

namespace jaco\action;

const OK = 1;
const VALIDATION_ERROR = 1;
const CORE_ERROR = 2;

function ok(int $code = OK): array
{
    return [$code];
}

function err(int $code, array $errors): array
{
    return [$code, $errors];
}

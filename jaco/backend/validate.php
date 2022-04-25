<?php

namespace jaco\validate;

function numeric(mixed $value): bool
{
    return is_numeric($value);
}

function required(mixed $value): bool
{
    if (is_null($value) || (is_string($value) && trim($value) === '')) {
        return false;
    }

    return true;
}

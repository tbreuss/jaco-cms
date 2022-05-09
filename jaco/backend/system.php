<?php

declare(strict_types=1);

namespace jaco\system;

const SECRETS = ['JACO_SECRET_KEY', 'JACO_PASSWORD'];

/**
 * @phpstan-return array<string,string>
 */
function defined_constants(): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if (substr($key, 0, 5) === 'JACO_' || substr($key, 0, 5) === 'jaco\\') {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if (in_array($key, SECRETS) !== false) {
                $value = '****';
            }
            $constants[$key] = $value;
        }
    }
    return $constants;
}

/**
 * @phpstan-return array<int,string>
 */
function defined_functions(): array
{
    $functions = [];
    foreach (get_defined_functions()['user'] as $function) {
        if (substr($function, 0, 5) === 'jaco\\') {
            $functions[] = $function;
        }
    }
    return $functions;
}

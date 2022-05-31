<?php

declare(strict_types=1);

namespace jaco\system;

const SECRETS = ['JACO_SECRET_KEY', 'JACO_PASSWORD'];

/**
 * @phpstan-return array<int,string>
 */
function defined_classes(): array
{
    $classes = [];
    foreach (get_declared_classes() as $class) {
        if (str_starts_with($class, 'jaco\\')) {
            $classes[] = $class;
        }
    }
    sort($classes);
    return $classes;
}

/**
 * @phpstan-return array<string,string>
 */
function defined_constants(): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if (str_starts_with($key, 'JACO_') || str_starts_with($key, 'jaco\\')) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if (in_array($key, SECRETS) !== false) {
                $value = '****';
            }
            $constants[$key] = $value;
        }
    }
    ksort($constants);
    return $constants;
}

/**
 * @phpstan-return array<int,string>
 */
function defined_functions(): array
{
    $functions = [];
    foreach (get_defined_functions()['user'] as $function) {
        if (str_starts_with($function, 'jaco\\')) {
            $functions[] = $function;
        }
    }
    sort($functions);
    return $functions;
}

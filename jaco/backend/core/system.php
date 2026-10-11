<?php declare(strict_types=1);

namespace jaco\core\system;

use jaco\core\config\Config;

function defined_classes(): array
{
    $classes = array_filter(
        get_declared_classes(),
        fn($class) => str_starts_with($class, 'jaco\\'),
    );
    sort($classes);

    return $classes;
}

function defined_constants(Config $config): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if (!str_starts_with($key, 'jaco\\')) {
            continue;
        }
        $constants[$key] = display_value($value, $config);
    }
    ksort($constants);
    return $constants;
}

function display_value(mixed $value, Config $config): mixed
{
    if (is_array($value)) {
        $value = json_encode($value);
    }
    if (is_string($value) && str_starts_with($value, $config->webDir)) {
        $value = substr($value, strlen($config->webDir));
    }
    return $value;
}

function defined_functions(): array
{
    $functions = array_filter(
        get_defined_functions()['user'],
        fn($class) => str_starts_with($class, 'jaco\\'),
    );
    sort($functions);

    return $functions;
}

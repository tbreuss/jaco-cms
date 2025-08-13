<?php declare(strict_types=1);

namespace jaco\core\system;

use const jaco\WEB_DIR;

const SECRETS = ['jaco\SECRET_KEY', 'jaco\PASSWORD'];

function defined_classes(): array
{
    $classes = array_filter(
        get_declared_classes(),
        fn($class) => str_starts_with($class, 'jaco\\'),
    );
    sort($classes);

    return $classes;
}

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
            if (is_string($value) && str_starts_with($value, WEB_DIR)) {
                $value = substr($value, strlen(WEB_DIR));
            }
            $constants[$key] = $value;
        }
    }
    ksort($constants);
    return $constants;
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

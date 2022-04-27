<?php

namespace jaco\system;

function defined_constants(): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if (substr($key, 0, 5) === 'JACO_') {
            $constants[$key] = $value;
        }
    }
    return $constants;
}

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

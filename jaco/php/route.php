<?php

namespace jaco\route;

use jaco\action;
use jaco\exception;

function get_default_action(): string
{
    return 'index';
}

function get_actions(): array
{
    return [
        'error',
        'index',
        'login',
        'logout',
        'page',
        'translations'
    ];
}

function get_action_str(): string
{
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    if (strlen($action) === 0) {
        return get_default_action();
    }
    if (!in_array($action, get_actions())) {
        throw new exception\ActionNotFound();
    }
    return $action;
}

function get_action(): string
{
    $action = get_action_str();
    $action_func = 'jaco\\action\\' . $action;

    if (function_exists($action_func)) {
        return $action_func;
    }
    throw new exception\ActionNotFound();
}

function get_error_action(): string
{
    return 'jaco\\action\\error';
}

function find_html_files(string $dir): array
{
    return [];
}

function call_action(string $action): array|string
{
    $function = new \ReflectionFunction($action);
    
    $parameters = [];
    foreach ($function->getParameters() as $parameter) {
        $name = $parameter->getName();
        if ($parameter->isOptional()) {
            $default = $parameter->getDefaultValue();
            $parameters[$name] = $default;
        }
        $value = filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING);
        if ($value) {
            $parameters[$name] = $value;
        }
    }

    return $action(...$parameters);
}

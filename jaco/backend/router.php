<?php

namespace jaco\router;

use jaco\auth;
use jaco\request;

function &routes(): array
{
    static $routes = [];
    return $routes;
}

function route(string $method, string $action, callable $callback, bool $authenticated): void
{
    $routes = &routes();
    $routes[] = [
        'method' => $method,
        'action' => $action,
        'callback' => $callback,
        'authenticated' => $authenticated
    ];
}

function get(string $action, callable $callback, bool $authenticated): void
{
    route('GET', $action, $callback, $authenticated);
}

function post(string $action, callable $callback, bool $authenticated): void
{
    route('POST', $action, $callback, $authenticated);
}

function dispatch(string $method, string $action): array
{
    $routes = routes();

    $routes_by_action = filter_routes($routes, 'action', $action);
    if (count($routes_by_action) === 0) {
        return [404, ['error' => '404 Page Not Found']];
    }

    $routes_by_method = filter_routes($routes_by_action, 'method', $method);
    if (count($routes_by_method) === 0) {
        return [405, ['error' => '405 Method Not Allowed']];
    }

    // take the first one if multiple routes exist
    foreach ($routes_by_method as $route) {
        if ($route['authenticated'] === true) {
            if (check_authorization() === false) {
                return [403, ['error' => '403 Forbidden']];
            }
        }
        $response = call_action($route['callback']);
        if ((count($response) === 2) && isset($response[0]) && is_int($response[0]) && isset($response[1])) {
            return $response;
        }
        return [200, $response];
    }

    return [400, ['error' => 'Bad Request']];
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

    return call_user_func($action, ...$parameters);
}

function check_authorization(): bool
{
    $header = request\get_header('Authorization');
    if (is_null($header)) {
        return false;
    }
    if (substr($header, 0, 6) !== 'Bearer') {
        return false;
    }
    $token = trim(substr($header, 7));
    return auth\is_logged_in($token);
}

function filter_routes(array $routes, string $column, string $search): array
{
    $matched = array_keys(array_column($routes, $column), $search);
    if (count($matched) === 0) {
        return [];
    }
    return array_values(array_intersect_key($routes, array_flip($matched)));
}

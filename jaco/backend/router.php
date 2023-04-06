<?php

declare(strict_types=1);

namespace jaco\router;

use jaco\auth;
use jaco\json;
use jaco\request;
use jaco\response;

/**
 * @phpstan-return array<int,array{method:string,action:string,callback:string,authenticated:bool}>
 */
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

/**
 * @phpstan-return array{int, array<int,array<string,array<string,string>|string>>}
 */
function dispatch(string $method, string $action): array
{
    $routes = routes();

    $routes_by_action = filter_routes($routes, 'action', $action);
    if (count($routes_by_action) === 0) {
        return [response\NOT_FOUND, [['title' => '404 Page Not Found']]];
    }

    $routes_by_method = filter_routes($routes_by_action, 'method', $method);
    if (count($routes_by_method) === 0) {
        return [response\METHOD_NOT_ALLOWED, [['title' => '405 Method Not Allowed']]];
    }

    // take the first one if multiple routes exist
    $route = reset($routes_by_method);

    if ($route['authenticated'] === true) {
        if (check_authorization() === false) {
            return [response\FORBIDDEN, [['title' => '403 Forbidden']]];
        }
    }

    $response = call_action($route['callback']);
    if (!is_array($response) || !in_array(count($response), [1, 2]) || !is_int($response[0])) {
        return [response\INTERNAL_SERVER_ERROR, [['title' => 'Invalid Action Response Payload']]];
    }

    return array_pad($response, 2, ''); // ensure array with two entries
}

/**
 * @phpstan-return string|array<mixed>
 */
function call_action(string $action): array|string
{
    $function = new \ReflectionFunction($action);

    $attributes = [];
    foreach ($function->getAttributes() as $attribute) {
        $attributeName = $attribute->getName();
        $attributes[$attributeName] = match($attributeName) {
            'json_schema_request', 'json_schema_response' => $attribute->getArguments()[0] ?? null,
            default => null
        };
    }

    $parameters = [];
    if (isset($attributes['json_schema_request'])) {
        $data = request\get_post_values();
        $errors = json\validate_data($data, $attributes['json_schema_request']);
        if (count($errors) > 0) {
            return [response\UNPROCESSABLE_ENTITY, $errors];
        }
        $parameters['data'] = $data;
    } else {
        foreach ($function->getParameters() as $parameter) {
            $name = $parameter->getName();
            if ($parameter->isOptional()) {
                $default = $parameter->getDefaultValue();
                $parameters[$name] = $default;
            }
            $value = filter_input(INPUT_GET, $name, FILTER_UNSAFE_RAW);
            if ($value) {
                $parameters[$name] = $value;
            }
        }
    }

    $response = call_user_func($action, ...$parameters);

    if (isset($attributes['json_schema_response'])) {
        $errors = json\validate_data($response, $attributes['json_schema_response']);
        if (count($errors) > 0) {
            return [response\UNPROCESSABLE_ENTITY, $errors];
        }
    }

    return $response;
}

function check_authorization(): bool
{
    $header = request\get_header_value('Authorization');
    if (is_null($header)) {
        return false;
    }
    if (!str_starts_with($header, 'Bearer')) {
        return false;
    }
    $token = trim(substr($header, 7));
    return auth\is_authenticated($token);
}

/**
 * @phpstan-param array<int,array{method:string,action:string,callback:string,authenticated:bool}> $routes
 * @phpstan-return array<int,array{method:string,action:string,callback:string,authenticated:bool}>
 */
function filter_routes(array $routes, string $column, string $search): array
{
    $matched = array_keys(array_column($routes, $column), $search);
    if (count($matched) === 0) {
        return [];
    }
    return array_values(array_intersect_key($routes, array_flip($matched)));
}

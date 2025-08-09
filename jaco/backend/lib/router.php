<?php

declare(strict_types=1);

namespace jaco\core\router;

use jaco\core\auth;
use jaco\core\http\request;
use jaco\core\http\response;
use jaco\core\http\Status;
use function jaco\core\http\json_response;
use function jaco\core\http\response\is_4xx;
use function jaco\core\http\response\is_5xx;

const VALIDATE_REQUEST_ATTRIBUTE = 'jaco\core\attribute\validate_request';
const VALIDATE_RESPONSE_ATTRIBUTE = 'jaco\core\attribute\validate_response';

function &routes(): array
{
    static $routes = [];
    return $routes;
}

function add(string $method, string $route, callable $callback, bool $authenticated, ?string $requestSchema = null, ?string $responseSchema = null): void
{
    $routes = &routes();
    $routes[] = [
        'method' => $method,
        'action' => $route,
        'callback' => $callback,
        'authenticated' => $authenticated,
        'requestJsonSchema' => $requestSchema,
        'responseJsonSchema' => $responseSchema,
    ];
}

function get(string $route, callable $callback, bool $authenticated, ?string $requestSchema = null, ?string $responseSchema = null): void
{
    add('GET', $route, $callback, $authenticated, $requestSchema, $responseSchema);
}

function post(string $route, callable $callback, bool $authenticated, ?string $requestSchema = null, ?string $responseSchema = null): void
{
    add('POST', $route, $callback, $authenticated, $requestSchema, $responseSchema);
}

function dispatch(string $method, string $action): Response
{
    $routes = routes();

    $routes_by_action = filter_routes($routes, 'action', $action);
    if (count($routes_by_action) === 0) {
        return json_response([['title' => '404 Page Not Found']], Status::NOT_FOUND);
    }

    $routes_by_method = filter_routes($routes_by_action, 'method', $method);
    if (count($routes_by_method) === 0) {
        return json_response([['title' => '405 Method Not Allowed']], Status::METHOD_NOT_ALLOWED);
    }

    // take the first one if multiple routes exist
    $route = reset($routes_by_method);

    if ($route['authenticated'] === true) {
        if (check_authorization() === false) {
            return json_response([['title' => '403 Forbidden']], Status::FORBIDDEN);
        }
    }

    return call_action($route['callback'], $route['requestJsonSchema'], $route['responseJsonSchema']);
}

function call_action(callable $action, ?string $requestJsonSchema, ?string $responseJsonSchema): Response
{
    $function = new \ReflectionFunction($action);

    $attributes = [];
    foreach ($function->getAttributes() as $attribute) {
        $attributeName = $attribute->getName();
        $attributes[$attributeName] = match ($attributeName) {
            VALIDATE_REQUEST_ATTRIBUTE, VALIDATE_RESPONSE_ATTRIBUTE => $attribute->getArguments()[0] ?? null,
            default => null
        };
    }

    // request body params
    if (isset($attributes[VALIDATE_REQUEST_ATTRIBUTE]) || ($requestJsonSchema !== null)) {
        $jsonSchema = $attributes[VALIDATE_REQUEST_ATTRIBUTE] ?? $requestJsonSchema;
        $bodyParams = request\get_post_values();
        $errors = request\validate($bodyParams, $jsonSchema);
        if (count($errors) > 0) {
            return json_response($errors, Status::BAD_REQUEST);
        }
    } else {
        $bodyParams = [];
    }

    $parameters = [];
    foreach ($function->getParameters() as $parameter) {
        $name = $parameter->getName();
        $type = $parameter->getType()->getName(); // @phpstan-ignore-line

        if ($parameter->isOptional()) {
            $default = $parameter->getDefaultValue();
            $parameters[$name] = $default;
        }

        if (in_array($name, ['bodyParams', 'apiInput']) && ($type === 'array')) {
            $parameters[$name] = $bodyParams;
        } else {
            $value = filter_input(INPUT_GET, $name, FILTER_UNSAFE_RAW);
            if ($value) {
                $parameters[$name] = $value;
            }
        }
    }

    try {
        /** @var Response $response */
        $response = call_user_func_array($action, $parameters);
    } catch (\TypeError $e) {
        return json_response([['detail' => $e->getMessage(), 'title' => 'Type Error']], Status::BAD_REQUEST);
    }

    if (is_4xx($response)) {
        $errors = response\validate($response->body, 'errors.json');
        if (count($errors) > 0) {
            return json_response($errors, Status::INTERNAL_SERVER_ERROR);
        }
    }

    if (is_5xx($response)) {
        $errors = response\validate($response->body, 'errors.json');
        if (count($errors) > 0) {
            return json_response($errors, Status::INTERNAL_SERVER_ERROR);
        }
    }

    if (isset($attributes[VALIDATE_RESPONSE_ATTRIBUTE])  || ($responseJsonSchema !== null)) {
        $jsonSchema = $attributes[VALIDATE_RESPONSE_ATTRIBUTE] ?? $responseJsonSchema;
        $errors = response\validate($response->body, $jsonSchema);
        if (count($errors) > 0) {
            #return json_response($errors, response\INTERNAL_SERVER_ERROR);
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

function filter_routes(array $routes, string $column, string $search): array
{
    $matched = array_keys(array_column($routes, $column), $search);
    if (count($matched) === 0) {
        return [];
    }
    return array_values(array_intersect_key($routes, array_flip($matched)));
}

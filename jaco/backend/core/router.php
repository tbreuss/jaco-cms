<?php declare(strict_types=1);

namespace jaco\core\router;

use Closure;
use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\core\responder;

use function jaco\core\http\json_response;
use function jaco\core\http\validate_request;
use function jaco\core\http\validate_response;

/**
 * Routes that keep working while maintenanceMode is on. Everything else that
 * changes data goes through a POST and is blocked there.
 */
const MAINTENANCE_EXCEPTIONS = ['login', 'system/change-password'];

/**
 * Routes a read-only account may still use although they are a POST. Everything
 * else that changes data is refused for it; reading stays available.
 */
const READ_ONLY_EXCEPTIONS = ['template/site/preview'];

function &routes(): array
{
    static $routes = [];
    return $routes;
}

function add(string $method, string $route, callable $callback, bool $authenticated, ?string $requestSchema = null, ?string $responseSchema = null, string $role = auth\ROLE_ADMIN): void
{
    $routes = &routes();
    $routes[] = [
        'method' => $method,
        'action' => $route,
        'callback' => $callback,
        'authenticated' => $authenticated,
        // fail closed: a route without an explicit role needs the highest one
        'role' => $role,
        'requestJsonSchema' => $requestSchema,
        'responseJsonSchema' => $responseSchema,
    ];
}

function get(string $route, callable $callback, bool $authenticated, ?string $requestSchema = null, ?string $responseSchema = null, string $role = auth\ROLE_ADMIN): void
{
    add('GET', $route, $callback, $authenticated, $requestSchema, $responseSchema, $role);
}

function post(string $route, callable $callback, bool $authenticated, ?string $requestSchema = null, ?string $responseSchema = null, string $role = auth\ROLE_ADMIN): void
{
    add('POST', $route, $callback, $authenticated, $requestSchema, $responseSchema, $role);
}

function dispatch(Request $request, Config $config): Response
{
    $actionQuery = (string) $request->query->val('action', '');
    $requestMethod = (string) $request->server->val('REQUEST_METHOD', '');
    $authorizationHeader = get_authorization_header($request);

    $routes = routes();

    $routes_by_action = filter_routes($routes, 'action', $actionQuery);
    if (count($routes_by_action) === 0) {
        return json_response([['title' => '404 Page Not Found']], Status::NOT_FOUND);
    }

    $routes_by_method = filter_routes($routes_by_action, 'method', $requestMethod);
    if (count($routes_by_method) === 0) {
        return json_response([['title' => '405 Method Not Allowed']], Status::METHOD_NOT_ALLOWED);
    }

    // take the first one if multiple routes exist
    $route = reset($routes_by_method);

    $claims = null;

    if ($route['authenticated'] === true) {
        $claims = check_authorization($config, $authorizationHeader);

        // 401 sends the frontend back to the login, 403 (role) must not: the session
        // itself is fine, only this action is not allowed
        if ($claims === null) {
            return json_response([['title' => '401 Unauthorized']], Status::UNAUTHORIZED);
        }

        if (!auth\role_allows($claims['role'], $route['role'])) {
            return json_response(
                [['title' => '403 Forbidden', 'detail' => 'Your role does not allow this.']],
                Status::FORBIDDEN,
            );
        }
    }

    // A read-only account keeps its role - usually admin - and only may not store:
    // the routes, the role and the editor stay the same, so the whole interface can
    // be shown while nothing can change.
    if ($claims !== null && $claims['readOnly'] === true
        && $requestMethod === 'POST'
        && !\in_array($actionQuery, READ_ONLY_EXCEPTIONS, true)) {
        return responder\read_only_response();
    }

    // Maintenance mode lives here, not in every action. Every route that changes
    // data is a POST, so the method decides and a new writing route cannot slip
    // past the brake by forgetting its own check. The two exceptions are the way
    // back in: the login itself and changing the password.
    if ($requestMethod === 'POST' && !\in_array($actionQuery, MAINTENANCE_EXCEPTIONS, true)) {
        $blocked = responder\maintenance_response($config->maintenanceMode);
        if ($blocked !== null) {
            return $blocked;
        }
    }

    return call_action($route['callback'], $request, $config, $route['requestJsonSchema'], $route['responseJsonSchema']);
}

function call_action(Closure $action, Request $request, Config $config, ?string $requestJsonSchema, ?string $responseJsonSchema): Response
{
    $function = new \ReflectionFunction($action);

    // request body params
    if ($requestJsonSchema !== null) {
        $errors = validate_request($request->post->values, $requestJsonSchema);
        if (count($errors) > 0) {
            return json_response($errors, Status::BAD_REQUEST);
        }
    }

    $parameters = [];

    foreach ($function->getParameters() as $parameter) {
        $name = $parameter->getName();
        $type = $parameter->getType()->getName(); // @phpstan-ignore-line
        if ($type === 'jaco\core\http\Request') {
            $parameters[$name] = $request;
        }
        if ($type === 'jaco\core\config\Config') {
            $parameters[$name] = $config;
        }
    }

    try {
        /** @var Response $response */
        $response = call_user_func_array($action, $parameters);
    } catch (\TypeError $e) {
        return json_response([['detail' => $e->getMessage(), 'title' => 'Type Error']], Status::BAD_REQUEST);
    }

    if ($response->isClientError() || $response->isServerError()) {
        $errors = validate_response($response->body, 'errors.json');
        if (count($errors) > 0) {
            return json_response($errors, Status::INTERNAL_SERVER_ERROR);
        }
        return $response;
    }

    if ($responseJsonSchema !== null) {
        $errors = validate_response($response->body, $responseJsonSchema);
        if (count($errors) > 0) {
            return json_response($errors, Status::INTERNAL_SERVER_ERROR);
        }
    }

    return $response;
}

/**
 * Claims of the token, or null when the header is missing or the token invalid.
 *
 * @return array{user: string, role: string, language: string, readOnly: bool}|null
 */
function check_authorization(Config $config, string $authorizationHeader): ?array
{
    if ($authorizationHeader === '') {
        return null;
    }

    $bearer = 'Bearer';

    if (!str_starts_with($authorizationHeader, $bearer)) {
        return null;
    }

    $token = trim(substr($authorizationHeader, strlen($bearer)));

    return auth\read_token($config, $token);
}

/**
 * Reads the Authorization header wherever the SAPI puts it: php -S and mod_php
 * expose HTTP_AUTHORIZATION, Apache with the .htaccess rewrite lands in
 * REDIRECT_HTTP_AUTHORIZATION, nginx/php-fpm only survive in the parsed headers.
 */
function get_authorization_header(Request $request): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        $value = (string) $request->server->val($key, '');
        if ($value !== '') {
            return $value;
        }
    }

    foreach ($request->headers->values as $name => $value) {
        if (\strtolower((string) $name) === 'authorization' && \is_string($value) && $value !== '') {
            return $value;
        }
    }

    return '';
}

function filter_routes(array $routes, string $column, string $search): array
{
    $matched = array_keys(array_column($routes, $column), $search);
    if (count($matched) === 0) {
        return [];
    }

    return array_values(array_intersect_key($routes, array_flip($matched)));
}

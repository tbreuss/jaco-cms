<?php

declare(strict_types=1);

namespace jaco\http\response;

use jaco\json;

const OK = 200;
const CREATED = 201;
const BAD_REQUEST = 400;
const FORBIDDEN = 403;
const NOT_FOUND = 404;
const METHOD_NOT_ALLOWED = 405;
const UNPROCESSABLE_ENTITY = 422;
const INTERNAL_SERVER_ERROR = 500;

function ok(mixed $data = null): array
{
    return [OK, $data];
}

function created(mixed $data = null): array
{
    return [CREATED, $data];
}

function bad_request(mixed $data): array
{
    return [BAD_REQUEST, $data];
}

function forbidden(mixed $data): array
{
    return [FORBIDDEN, $data];
}

function not_found(mixed $data): array
{
    return [NOT_FOUND, $data];
}

function method_not_allowed(mixed $data): array
{
    return [METHOD_NOT_ALLOWED, $data];
}

function unprocessable_entity(array $data): array
{
    return [UNPROCESSABLE_ENTITY, $data];
}

function internal_server_error(array $data): array
{
    return [INTERNAL_SERVER_ERROR, $data];
}

function validate(array|string|null $data, string $schema): array
{
    return json\schema\validate_data($data, $schema);
}

function client_error(int $status, array $data = null): array
{
    if ($status < 400 || $status >= 500) {
        return internal_server_error($data);
    }
    return [$status, $data];
}

function server_error(int $status, array $data = null): array
{
    if ($status < 500 || $status >= 600) {
        return internal_server_error($data);
    }
    return [$status, $data];
}

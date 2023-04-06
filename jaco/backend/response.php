<?php

declare(strict_types=1);

namespace jaco\response;

const OK = 200;
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

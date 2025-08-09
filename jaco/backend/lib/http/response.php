<?php

declare(strict_types=1);

namespace jaco\core\http;

enum Status: int
{
    case OK = 200;
    case CREATED = 201;
    case BAD_REQUEST = 400;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case UNPROCESSABLE_ENTITY = 422;
    case INTERNAL_SERVER_ERROR = 500;
}

final class Response
{
    public function __construct(
        public Status $status,
        /** @var array<string, string> */
        public array $headers = [],
        public ?string $body = null,
    ) {}
}

function html_response(?string $body = null, Status $status = Status::OK, array $headers = []): Response
{
    return new Response(
        status: $status,
        headers: array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers),
        body: $body,
    );
}

function json_response(mixed $data = null, Status $status = Status::OK, array $headers = []): Response
{
    return new Response(
        status: $status,
        headers: array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers),
        body: json_encode($data),
    );
}

namespace jaco\core\http\response;

use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\core\json;

function ok(mixed $data = null): array
{
    return [Status::OK, $data];
}

function created(mixed $data = null): array
{
    return [Status::CREATED, $data];
}

function bad_request(mixed $data): array
{
    return [Status::BAD_REQUEST, $data];
}

function forbidden(mixed $data): array
{
    return [Status::FORBIDDEN, $data];
}

function not_found(mixed $data): array
{
    return [Status::NOT_FOUND, $data];
}

function method_not_allowed(mixed $data): array
{
    return [Status::METHOD_NOT_ALLOWED, $data];
}

function unprocessable_entity(array $data): array
{
    return [Status::UNPROCESSABLE_ENTITY, $data];
}

function internal_server_error(array $data): array
{
    return [Status::INTERNAL_SERVER_ERROR, $data];
}

function validate(array|string|null $data, string $schema): array
{
    return json\schema\validate_data($data, $schema);
}

function is_2xx(Response $responseOrStatus): bool
{
    $status = $responseOrStatus->status->value;
    return $status >= 200 && $status < 300;
}

function is_3xx(Response $responseOrStatus): bool
{
    $status = $responseOrStatus->status->value;
    return $status >= 300 && $status < 400;
}

function is_4xx(Response $responseOrStatus): bool
{
    $status = $responseOrStatus->status->value;
    return $status >= 400 && $status < 500;
}

function is_5xx(Response $responseOrStatus): bool
{
    $status = $responseOrStatus->status->value;
    return $status >= 500 && $status < 600;
}

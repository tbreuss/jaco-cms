<?php declare(strict_types=1);

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
use jaco\core\json;

function validate(array|string|null $data, string $schema): array
{
    return json\schema\validate_data($data, $schema);
}

function is_successful(Response $response): bool
{
    $status = (string) $response->status->value;
    return str_starts_with($status, '2');
}

function is_redirection(Response $response): bool
{
    $status = (string) $response->status->value;
    return str_starts_with($status, '3');
}

function is_client_error(Response $response): bool
{
    $status = (string) $response->status->value;
    return str_starts_with($status, '4');
}

function is_server_error(Response $response): bool
{
    $status = (string) $response->status->value;
    return str_starts_with($status, '5');
}

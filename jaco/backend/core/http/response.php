<?php declare(strict_types=1);

namespace jaco\core\http;

use jaco\core\json;

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
    case SERVICE_UNAVAILABLE = 503;
}

final class Response
{
    public function __construct(
        public Status $status,
        /** @var array<string, string> */
        public array $headers = [],
        public ?string $body = null,
    ) {}


    public function isSuccessful(): bool
    {
        $status = (string) $this->status->value;
        return str_starts_with($status, '2');
    }

    public function isRedirection(): bool
    {
        $status = (string) $this->status->value;
        return str_starts_with($status, '3');
    }

    public function isClientError(): bool
    {
        $status = (string) $this->status->value;
        return str_starts_with($status, '4');
    }

    public function isServerError(): bool
    {
        $status = (string) $this->status->value;
        return str_starts_with($status, '5');
    }
}

function json_response(mixed $data = null, Status $status = Status::OK, array $headers = []): Response
{
    return new Response(
        status: $status,
        headers: array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers),
        body: encode_json($data),
    );
}

function encode_json(mixed $data): string
{
    try {
        return \json_encode($data, \JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        // e.g. data contains invalid UTF-8 sequences
        return '[{"title": "Internal Server Error", "detail": "Could not encode data as JSON."}]';
    }
}

function validate_response(array|string|null $data, string $schema): array
{
    return json\validate_data($data, $schema);
}

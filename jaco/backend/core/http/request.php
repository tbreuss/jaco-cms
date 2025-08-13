<?php declare(strict_types=1);

namespace jaco\core\http;

final class Map
{
    public function __construct(
        public readonly array $values,
    ) {}

    public function val(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function intVal(string $key, int $default = 0): int
    {
        return (int) $this->values[$key] ?? $default;
    }

    public function strVal(string $key, string $default = ''): string
    {
        return (string) $this->values[$key] ?? $default;
    }
}

final class Request
{
    public function __construct(
        public readonly Map $query,    // $_GET,
        public readonly Map $post,     // php://input
        public readonly Map $server,   // $_SERVER
        public readonly Map $headers,  //
    ) {}

    public static function fromGlobals(): self
    {
        function get_post_values(): array
        {
            $contents = file_get_contents('php://input');
            if (($contents === false) || (strlen($contents) === 0)) {
                return [];
            }
            $decoded = json_decode($contents, true);
            if (!is_array($decoded)) {
                return [];
            }
            return $decoded;
        }

        return new Request(
            query: new Map($_GET),
            post: new Map(get_post_values()),
            server: new Map($_SERVER),
            headers: new Map(apache_request_headers()),
        );
    }
}

namespace jaco\core\http\request;

use jaco\core\json;

function get_post_values(): array
{
    $contents = file_get_contents('php://input');
    if (($contents === false) || (strlen($contents) === 0)) {
        return [];
    }
    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function get_post_value(string $name, string $default): string
{
    $values = get_post_values();
    return $values[$name] ?? $default;
}

function get_header_value(string $header): ?string
{
    $headers = apache_request_headers();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === strtolower($header)) {
            return $value;
        }
    }
    return null;
}

function validate(array|string $data, string $schema): array
{
    return json\schema\validate_data($data, $schema);
}

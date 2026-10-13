<?php declare(strict_types=1);

namespace jaco\core\http;

use jaco\core\json;

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
        return (int) ($this->values[$key] ?? $default);
    }

    public function strVal(string $key, string $default = ''): string
    {
        return (string) ($this->values[$key] ?? $default);
    }
}

final class Request
{
    public function __construct(
        public readonly Map $query,
        public readonly Map $post,
        public readonly Map $server,
        public readonly Map $headers,
    ) {}

    public static function fromGlobals(): self
    {
        return new Request(
            query: new Map($_GET),
            post: new Map(get_post_values()),
            server: new Map($_SERVER),
            headers: new Map(request_headers()),
        );
    }
}

/**
 * apache_request_headers only exists with mod_php and the built-in server,
 * parse $_SERVER so nginx and php-fpm work as well.
 *
 * @return array<string, string>
 */
function request_headers(): array
{
    if (function_exists('apache_request_headers')) {
        return apache_request_headers();
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (is_string($key) && is_string($value) && str_starts_with($key, 'HTTP_')) {
            $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$name] = $value;
        }
    }

    if (isset($_SERVER['CONTENT_TYPE']) && is_string($_SERVER['CONTENT_TYPE'])) {
        $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
    }

    if (isset($_SERVER['CONTENT_LENGTH']) && is_string($_SERVER['CONTENT_LENGTH'])) {
        $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
    }

    return $headers;
}

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

function validate_request(array|string $data, string $schema): array
{
    return json\validate_data($data, $schema);
}

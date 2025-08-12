<?php declare(strict_types=1);

namespace jaco\core\http;

final class Request
{
    public function __construct(
        public array $query, // $_GET,
        public array $post, // php://input
        public array $server, // $_SERVER
        public array $headers, //
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
            query: $_GET,
            post: get_post_values(),
            server: $_SERVER,
            headers: apache_request_headers(),
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

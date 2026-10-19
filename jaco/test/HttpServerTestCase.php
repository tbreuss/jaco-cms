<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;

/**
 * Starts php's built in server on the repository root for tests that need a real http
 * request: a PHP_SAPI guard or a response header cannot be observed from inside the
 * process. The port comes from the operating system, so parallel runs do not collide.
 */
abstract class HttpServerTestCase extends TestCase
{
    /** @var resource|null */
    private static $server = null;

    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();

        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, '-t', dirname(__DIR__, 2)],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );

        if ($server === false) {
            self::fail('could not start the built in server');
        }

        self::$server = $server;

        self::waitForServer();
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        self::$server = null;
    }

    /**
     * @return array{int, array<string, string>, string} status, headers with lower-cased
     *                                                   names, body
     */
    protected static function request(string $method, string $path, string $body = ''): array
    {
        $context = stream_context_create(['http' => [
            'method' => $method,
            'ignore_errors' => true,
            'timeout' => 5,
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
        ]]);

        // the response headers come from the stream metadata: $http_response_header is
        // deprecated as of php 8.5 and http_get_last_response_headers() does not exist
        // before 8.4, the project supports 8.2 upwards
        // the one place where suppression is right: while the server starts, every poll
        // fails and a warning per attempt would bury the real failures
        $handle = @fopen('http://127.0.0.1:' . self::$port . $path, 'rb', false, $context);
        if ($handle === false) {
            return [0, [], ''];
        }

        $contents = (string) stream_get_contents($handle);
        $meta = stream_get_meta_data($handle);
        fclose($handle);

        $lines = is_array($meta['wrapper_data']) ? $meta['wrapper_data'] : [];

        $status = preg_match('#^HTTP/\S+\s+(\d{3})#', (string) ($lines[0] ?? ''), $match) === 1 ? (int) $match[1] : 0;

        $headers = [];
        foreach (array_slice($lines, 1) as $line) {
            $parts = explode(':', (string) $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return [$status, $headers, $contents];
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if ($socket === false) {
            self::fail('could not reserve a port: ' . $errorMessage);
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr((string) $name, strrpos((string) $name, ':') + 1);
    }

    private static function waitForServer(): void
    {
        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            [$status] = self::request('GET', '/index.html');
            if ($status === 200) {
                return;
            }
            usleep(100_000);
        }

        self::fail('the built in server did not come up on port ' . self::$port);
    }
}

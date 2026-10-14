<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * The cli entry point must not answer web requests: it would print version and log
 * output to anyone asking. The guard is a PHP_SAPI check, so only a real http request
 * shows it – these tests start php's built in server on the repository root.
 */
#[TestDox('CLI entry point')]
final class CliEntryPointTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;

    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();
        $documentRoot = dirname(__DIR__, 2);

        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, '-t', $documentRoot],
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

    public function testAWebRequestIsRefused(): void
    {
        $this->assertSame(403, self::httpStatus('/jaco/cli.php'));
    }

    public function testTheServerItselfAnswers(): void
    {
        // proves the 403 above comes from the guard, not from a server that never started
        $this->assertSame(200, self::httpStatus('/index.html'));
    }

    private static function httpStatus(string $path): int
    {
        $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
        @file_get_contents('http://127.0.0.1:' . self::$port . $path, false, $context);

        $statusLine = $http_response_header[0] ?? '';

        return preg_match('#^HTTP/\S+\s+(\d{3})#', $statusLine, $match) === 1 ? (int) $match[1] : 0;
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
            if (self::httpStatus('/index.html') === 200) {
                return;
            }
            usleep(100_000);
        }

        self::fail('the built in server did not come up on port ' . self::$port);
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * The cli entry point must not answer web requests: it would print version and log
 * output to anyone asking. The guard is a PHP_SAPI check, so only a real http request
 * shows it.
 */
#[TestDox('CLI entry point')]
final class CliEntryPointTest extends HttpServerTestCase
{
    public function testAWebRequestIsRefused(): void
    {
        [$status] = self::request('GET', '/jaco/cli.php');

        $this->assertSame(403, $status);
    }

    public function testTheServerItselfAnswers(): void
    {
        // proves the 403 above comes from the guard, not from a server that never started
        [$status] = self::request('GET', '/index.html');

        $this->assertSame(200, $status);
    }
}

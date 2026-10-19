<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Every api response carries the maintenance flag as a header, so the frontend can show
 * its banner without polling a dedicated endpoint. Only a real request shows it: the
 * entry point adds the header after the router answered, a path no in-process test takes.
 */
#[TestDox('API entry point')]
final class ApiEntryPointTest extends HttpServerTestCase
{
    public function testARoutedResponseCarriesTheMaintenanceFlag(): void
    {
        [$status, $headers] = self::request(
            'POST',
            '/jaco/api.php?action=login',
            '{"username":"nobody","password":"wrong"}',
        );

        $this->assertNotSame(0, $status, 'the server answered at all');
        $this->assertArrayHasKey('x-maintenance-mode', $headers);
        $this->assertContains($headers['x-maintenance-mode'], ['true', 'false']);
    }

    public function testAnUnroutedResponseCarriesItToo(): void
    {
        // proving "every response", not just the ones that reach an action
        [$status, $headers] = self::request('POST', '/jaco/api.php?action=does-not-exist');

        $this->assertSame(404, $status);
        $this->assertArrayHasKey('x-maintenance-mode', $headers);
    }
}

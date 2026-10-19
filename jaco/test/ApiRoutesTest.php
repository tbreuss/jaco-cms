<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Guards the route table in jaco/routes.php: a public route must work without a token,
 * a private route must not.
*/
#[TestDox('Route table (jaco/routes.php)')]
final class ApiRoutesTest extends ApiTestCase
{
    public function testPublicIndexRouteIsRegisteredAndResponds(): void
    {
        $response = $this->api('index', [], 'GET', false);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertArrayHasKey('name', $body);
        $this->assertArrayHasKey('version', $body);
        $this->assertArrayNotHasKey('phpVersion', $body, 'the public index must not expose the PHP version');
    }

    public function testAboutRouteRequiresAToken(): void
    {
        $response = $this->api('about', [], 'GET', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    public function testAboutRouteReturnsTheVersionDataForARole(): void
    {
        $response = $this->api('about', [], 'GET', true);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertArrayHasKey('name', $body);
        $this->assertArrayHasKey('version', $body);
        $this->assertArrayHasKey('phpVersion', $body);
    }

    public function testPrivateRouteIsForbiddenWithoutToken(): void
    {
        $response = $this->api('page/list', [], 'GET', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    public function testUnknownRouteIsNotFound(): void
    {
        $response = $this->api('does/not/exist', [], 'GET', false);

        $this->assertSame(Status::NOT_FOUND, $response->status);
    }

    public function testKnownRouteWithWrongMethodIsNotAllowed(): void
    {
        // page/update is registered as POST only
        $response = $this->api('page/update', [], 'GET', false);

        $this->assertSame(Status::METHOD_NOT_ALLOWED, $response->status);
    }
}

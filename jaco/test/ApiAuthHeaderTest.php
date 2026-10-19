<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\auth;
use jaco\core\http\Map;
use jaco\core\http\Request;
use jaco\core\http\Status;
use jaco\core\router;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Authorization header on every SAPI')]
final class ApiAuthHeaderTest extends ApiTestCase
{
    public function testTokenInHttpAuthorization(): void
    {
        $this->assertSame(Status::OK, $this->dispatchWith([], ['HTTP_AUTHORIZATION' => $this->bearer()])->status);
    }

    public function testTokenInRedirectHttpAuthorization(): void
    {
        $this->assertSame(Status::OK, $this->dispatchWith([], ['REDIRECT_HTTP_AUTHORIZATION' => $this->bearer()])->status);
    }

    public function testTokenInParsedHeaders(): void
    {
        $this->assertSame(Status::OK, $this->dispatchWith(['Authorization' => $this->bearer()])->status);
    }

    public function testMissingTokenIsForbidden(): void
    {
        $this->assertSame(Status::UNAUTHORIZED, $this->dispatchWith()->status);
    }

    /** @param array<string, string> $headers @param array<string, string> $server */
    private function dispatchWith(array $headers = [], array $server = []): \jaco\core\http\Response
    {
        return router\dispatch(new Request(
            query: new Map(['action' => 'page/list']),
            post: new Map([]),
            server: new Map(array_merge(['REQUEST_METHOD' => 'GET'], $server)),
            headers: new Map($headers),
        ), $this->config);
    }

    private function bearer(): string
    {
        return 'Bearer ' . auth\authenticate($this->config, TEST_USERNAME, TEST_PASSWORD);
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Stylesheet view API (css/view)')]
final class ApiCssViewTest extends ApiTestCase
{
    private const FILE = 'api-css-view-test.css';
    private const CONTENT = "h1 {\n    color: red;\n}\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        file_put_contents($this->config->webDir . '/' . self::FILE, self::CONTENT);
    }

    public function testStylesheetIsReturnedWithItsContent(): void
    {
        $response = $this->api('css/view', method: 'GET', query: ['file' => self::FILE]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::FILE, $body['file']);
        $this->assertSame(self::CONTENT, $body['content']);
        $this->assertSame(filemtime($this->config->webDir . '/' . self::FILE), $body['created']);
    }

    public function testMissingStylesheetIsRejected(): void
    {
        $response = $this->api('css/view', method: 'GET', query: ['file' => 'does-not-exist.css']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testNonCssFileIsRejected(): void
    {
        $response = $this->api('css/view', method: 'GET', query: ['file' => 'index.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('css/view', method: 'GET', authenticated: false, query: ['file' => self::FILE]);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

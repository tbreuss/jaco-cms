<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Content template API (template/content)')]
final class ApiTemplateContentTest extends ApiTestCase
{
    private const TEMPLATE = 'api-content-list.html';

    private const CONTENT = "<div class=\"news-section\">\n<h2 data-jaco-line>Headline</h2>\n</div>\n";

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->config->templateContentDir . '/' . self::TEMPLATE, self::CONTENT);
    }

    protected function tearDown(): void
    {
        @unlink($this->config->templateContentDir . '/' . self::TEMPLATE);

        parent::tearDown();
    }

    public function testTemplatesAreListedWithTheirFile(): void
    {
        $response = $this->api('template/content', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $items = (array) json_decode((string) $response->body, true);
        $item = $this->itemWithFile($items, self::TEMPLATE);

        // fragments carry no title, so the file name is used
        $this->assertSame(self::TEMPLATE, $item['title']);
        $this->assertIsInt($item['created']);
    }

    public function testTemplateContentIsReturned(): void
    {
        $response = $this->api('template/content/view', method: 'GET', query: ['file' => self::TEMPLATE]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::TEMPLATE, $body['file']);
        $this->assertSame(self::CONTENT, $body['content']);
        $this->assertIsInt($body['created']);
    }

    public function testMissingTemplateIsRejected(): void
    {
        $response = $this->api('template/content/view', method: 'GET', query: ['file' => 'does-not-exist.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testParentSegmentsAreRejected(): void
    {
        $response = $this->api('template/content/view', method: 'GET', query: ['file' => '../secret.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testNonHtmlFileIsRejected(): void
    {
        $response = $this->api('template/content/view', method: 'GET', query: ['file' => 'index.css']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testListRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('template/content', method: 'GET', authenticated: false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    public function testViewRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('template/content/view', method: 'GET', authenticated: false, query: ['file' => self::TEMPLATE]);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

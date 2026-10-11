<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page template API (template/pages)')]
final class ApiTemplatePagesTest extends ApiTestCase
{
    private const TEMPLATE = 'api-template-list.html';

    private const CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Template list title</title></head>"
        . "<body></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->config->templatePageDir . '/' . self::TEMPLATE, self::CONTENT);
    }

    protected function tearDown(): void
    {
        @unlink($this->config->templatePageDir . '/' . self::TEMPLATE);

        parent::tearDown();
    }

    public function testTemplatesAreListedWithTheirTitle(): void
    {
        $response = $this->api('template/pages', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $items = (array) json_decode((string) $response->body, true);
        $item = $this->itemWithFile($items, self::TEMPLATE);

        $this->assertSame('Template list title', $item['title']);
        $this->assertIsInt($item['created']);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('template/pages', method: 'GET', authenticated: false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

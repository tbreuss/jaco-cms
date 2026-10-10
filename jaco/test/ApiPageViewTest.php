<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\WEB_DIR;

/**
 * @testdox Page view API (page/view)
 */
final class ApiPageViewTest extends ApiTestCase
{
    private const PAGE = 'api-view-test.html';

    private const CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>View test</title></head>"
        . "<body><p>body</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        file_put_contents(WEB_DIR . '/' . self::PAGE, self::CONTENT);
        file_put_contents(WEB_DIR . '/api-view-test.txt', 'not a page');
    }

    public function testPageIsReturnedWithItsContent(): void
    {
        $response = $this->api('page/view', method: 'GET', query: ['file' => self::PAGE]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::PAGE, $body['file']);
        $this->assertSame('View test', $body['title']);
        $this->assertSame(self::CONTENT, $body['content']);
        $this->assertSame(filemtime(WEB_DIR . '/' . self::PAGE), $body['created']);
    }

    public function testUnknownPageIsRejected(): void
    {
        $response = $this->api('page/view', method: 'GET', query: ['file' => 'does-not-exist.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testFileWithoutPageExtensionIsRejected(): void
    {
        $response = $this->api('page/view', method: 'GET', query: ['file' => 'api-view-test.txt']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testPageInTheAppDirIsNotReadable(): void
    {
        $protected = APP_DIR . '/api-view-protected.html';
        file_put_contents($protected, '<p>app internals</p>');

        try {
            $response = $this->api('page/view', method: 'GET', query: [
                'file' => APP_DIR_NAME . '/api-view-protected.html',
            ]);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertStringNotContainsString('app internals', (string) $response->body);
        } finally {
            @unlink($protected);
        }
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/view', method: 'GET', authenticated: false, query: ['file' => self::PAGE]);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

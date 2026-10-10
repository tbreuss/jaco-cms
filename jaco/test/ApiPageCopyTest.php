<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\WEB_DIR;

/**
 * @testdox Page copy API (page/copy)
 */
final class ApiPageCopyTest extends ApiTestCase
{
    private const SOURCE = 'api-copy-source.html';
    private const TARGET = 'api-copy-target.html';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        file_put_contents(WEB_DIR . '/' . self::SOURCE, $this->page('Source title', 'source body'));
    }

    public function testPageIsCopiedWithANewTitle(): void
    {
        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => self::TARGET,
            'dstPageTitle' => 'Copied title',
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::TARGET, $body['file']);
        $this->assertSame('Copied title', $body['title']);
        $this->assertIsInt($body['created']);

        $copy = (string) file_get_contents(WEB_DIR . '/' . self::TARGET);
        $this->assertStringContainsString('<title>Copied title</title>', $copy);
        $this->assertStringContainsString('source body', $copy);

        // the source keeps its own title
        $this->assertStringContainsString(
            '<title>Source title</title>',
            (string) file_get_contents(WEB_DIR . '/' . self::SOURCE),
        );
    }

    public function testExistingTargetIsNotOverwritten(): void
    {
        file_put_contents(WEB_DIR . '/' . self::TARGET, 'existing content');

        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => self::TARGET,
            'dstPageTitle' => 'Copied title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('existing content', file_get_contents(WEB_DIR . '/' . self::TARGET));
    }

    public function testTargetInTheAppDirIsRejected(): void
    {
        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => APP_DIR_NAME . '/' . self::TARGET,
            'dstPageTitle' => 'Copied title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist(APP_DIR . '/' . self::TARGET);
    }

    public function testSourceInTheAppDirIsRejected(): void
    {
        $source = APP_DIR . '/api-copy-protected.html';
        file_put_contents($source, '<p>app internals</p>');

        try {
            $response = $this->api('page/copy', [
                'srcFileName' => APP_DIR_NAME . '/api-copy-protected.html',
                'dstFileName' => self::TARGET,
                'dstPageTitle' => 'Copied title',
            ]);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertFileDoesNotExist(WEB_DIR . '/' . self::TARGET);
        } finally {
            @unlink($source);
        }
    }

    public function testUnknownSourceIsRejected(): void
    {
        $response = $this->api('page/copy', [
            'srcFileName' => 'does-not-exist.html',
            'dstFileName' => self::TARGET,
            'dstPageTitle' => 'Copied title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/' . self::TARGET);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => self::TARGET,
            'dstPageTitle' => 'Copied title',
        ], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/' . self::TARGET);
    }

    private function page(string $title, string $body): string
    {
        return "<!DOCTYPE html>\n<html lang=\"en\"><head><title>" . $title . "</title></head><body>"
            . $body . "</body></html>\n";
    }
}

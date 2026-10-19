<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page copy API (page/copy)')]
final class ApiPageCopyTest extends ApiTestCase
{
    private const SOURCE = 'api-copy-source.html';
    private const TARGET = 'api-copy-target.html';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        file_put_contents($this->config->webDir . '/' . self::SOURCE, $this->page('Source title', 'source body'));
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

        $copy = (string) file_get_contents($this->config->webDir . '/' . self::TARGET);
        $this->assertStringContainsString('<title>Copied title</title>', $copy);
        $this->assertStringContainsString('source body', $copy);

        // the source keeps its own title
        $this->assertStringContainsString(
            '<title>Source title</title>',
            (string) file_get_contents($this->config->webDir . '/' . self::SOURCE),
        );
    }

    public function testExistingTargetIsNotOverwritten(): void
    {
        file_put_contents($this->config->webDir . '/' . self::TARGET, 'existing content');

        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => self::TARGET,
            'dstPageTitle' => 'Copied title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('existing content', file_get_contents($this->config->webDir . '/' . self::TARGET));
    }

    public function testTargetInTheAppDirIsRejected(): void
    {
        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => $this->config->appDirName . '/' . self::TARGET,
            'dstPageTitle' => 'Copied title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist($this->config->appDir . '/' . self::TARGET);
    }

    public function testSourceInTheAppDirIsRejected(): void
    {
        $source = $this->config->appDir . '/api-copy-protected.html';
        file_put_contents($source, '<p>app internals</p>');

        try {
            $response = $this->api('page/copy', [
                'srcFileName' => $this->config->appDirName . '/api-copy-protected.html',
                'dstFileName' => self::TARGET,
                'dstPageTitle' => 'Copied title',
            ]);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertFileDoesNotExist($this->config->webDir . '/' . self::TARGET);
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
        $this->assertFileDoesNotExist($this->config->webDir . '/' . self::TARGET);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/copy', [
            'srcFileName' => self::SOURCE,
            'dstFileName' => self::TARGET,
            'dstPageTitle' => 'Copied title',
        ], 'POST', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
        $this->assertFileDoesNotExist($this->config->webDir . '/' . self::TARGET);
    }

    private function page(string $title, string $body): string
    {
        return "<!DOCTYPE html>\n<html lang=\"en\"><head><title>" . $title . "</title></head><body>"
            . $body . "</body></html>\n";
    }
}

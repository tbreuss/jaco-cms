<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page delete API (page/delete)')]
final class ApiPageDeleteTest extends ApiTestCase
{
    private const PAGE = 'api-delete-test.html';

    private const CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Delete test</title></head>"
        . "<body><p>keep</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);

        file_put_contents($this->config->webDir . '/' . self::PAGE, self::CONTENT);
    }

    public function testPageIsBackedUpBeforeItIsDeleted(): void
    {
        $response = $this->api('page/delete', ['fileName' => self::PAGE]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist($this->config->webDir . '/' . self::PAGE);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertStringContainsString(self::PAGE, $backups[0]);
        $this->assertSame(self::CONTENT, file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testUnknownPageIsRejected(): void
    {
        $response = $this->api('page/delete', ['fileName' => 'does-not-exist.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testFileWithoutPageExtensionIsNotDeleted(): void
    {
        $file = $this->config->webDir . '/api-delete-test.txt';
        file_put_contents($file, 'not a page');

        $response = $this->api('page/delete', ['fileName' => 'api-delete-test.txt']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('not a page', file_get_contents($file));
    }

    public function testFileInTheAppDirIsNotDeleted(): void
    {
        $file = $this->config->appDir . '/api-delete-protected.html';
        file_put_contents($file, 'admin ui');

        try {
            $response = $this->api('page/delete', [
                'fileName' => $this->config->appDirName . '/api-delete-protected.html',
            ]);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertFileExists($file, 'the app directory must be out of reach');
        } finally {
            @unlink($file);
        }
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/delete', ['fileName' => self::PAGE], 'POST', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
        $this->assertFileExists($this->config->webDir . '/' . self::PAGE);
    }
}

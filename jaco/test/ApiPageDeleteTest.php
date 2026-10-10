<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;

/**
 * @testdox Page delete API (page/delete)
 */
final class ApiPageDeleteTest extends ApiTestCase
{
    private const PAGE = 'api-delete-test.html';

    private const CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Delete test</title></head>"
        . "<body><p>keep</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        $this->watchDir(BACKUP_DIR);

        file_put_contents(WEB_DIR . '/' . self::PAGE, self::CONTENT);
    }

    public function testPageIsBackedUpBeforeItIsDeleted(): void
    {
        $response = $this->api('page/delete', ['fileName' => self::PAGE]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/' . self::PAGE);

        $backups = $this->newEntriesIn(BACKUP_DIR);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertStringContainsString(self::PAGE, $backups[0]);
        $this->assertSame(self::CONTENT, file_get_contents(BACKUP_DIR . '/' . $backups[0]));
    }

    public function testUnknownPageIsRejected(): void
    {
        $response = $this->api('page/delete', ['fileName' => 'does-not-exist.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testFileWithoutPageExtensionIsNotDeleted(): void
    {
        $file = WEB_DIR . '/api-delete-test.txt';
        file_put_contents($file, 'not a page');

        $response = $this->api('page/delete', ['fileName' => 'api-delete-test.txt']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('not a page', file_get_contents($file));
    }

    public function testFileInTheAppDirIsNotDeleted(): void
    {
        $file = APP_DIR . '/api-delete-protected.html';
        file_put_contents($file, 'admin ui');

        try {
            $response = $this->api('page/delete', [
                'fileName' => APP_DIR_NAME . '/api-delete-protected.html',
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

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileExists(WEB_DIR . '/' . self::PAGE);
    }
}

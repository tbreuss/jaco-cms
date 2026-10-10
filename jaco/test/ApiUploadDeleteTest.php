<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_DIR;
use const jaco\WEB_DIR;

/**
 * @testdox Upload delete API (upload/delete)
 */
final class ApiUploadDeleteTest extends ApiTestCase
{
    private const UPLOAD = 'upload/api-upload-delete-test.jpg';
    private const CONTENT = 'not really an image, but the extension is what counts';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        $this->watchDir(BACKUP_DIR);
        $this->watchDir(UPLOAD_DIR);

        file_put_contents(WEB_DIR . '/' . self::UPLOAD, self::CONTENT);
    }

    public function testUploadIsBackedUpBeforeItIsDeleted(): void
    {
        $response = $this->api('upload/delete', ['fileName' => self::UPLOAD]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/' . self::UPLOAD);

        $backups = $this->newEntriesIn(BACKUP_DIR);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertStringContainsString('upload|api-upload-delete-test.jpg', $backups[0]);
        $this->assertSame(self::CONTENT, file_get_contents(BACKUP_DIR . '/' . $backups[0]));
    }

    public function testUploadWithDisallowedExtensionIsNotDeleted(): void
    {
        $file = WEB_DIR . '/upload/api-upload-delete-test.php';
        file_put_contents($file, '<?php // must survive');

        try {
            $response = $this->api('upload/delete', ['fileName' => 'upload/api-upload-delete-test.php']);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertFileExists($file);
        } finally {
            @unlink($file);
        }
    }

    public function testUnknownUploadIsRejected(): void
    {
        $response = $this->api('upload/delete', ['fileName' => 'upload/does-not-exist.jpg']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('upload/delete', ['fileName' => self::UPLOAD], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileExists(WEB_DIR . '/' . self::UPLOAD);
    }
}

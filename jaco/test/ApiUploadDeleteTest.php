<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

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

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        $this->watchDir($this->config->uploadDir);

        file_put_contents($this->config->webDir . '/' . self::UPLOAD, self::CONTENT);
    }

    public function testUploadIsBackedUpBeforeItIsDeleted(): void
    {
        $response = $this->api('upload/delete', ['fileName' => self::UPLOAD]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist($this->config->webDir . '/' . self::UPLOAD);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertStringContainsString('upload|api-upload-delete-test.jpg', $backups[0]);
        $this->assertSame(self::CONTENT, file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testUploadWithDisallowedExtensionIsNotDeleted(): void
    {
        $file = $this->config->webDir . '/upload/api-upload-delete-test.php';
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
        $this->assertFileExists($this->config->webDir . '/' . self::UPLOAD);
    }
}

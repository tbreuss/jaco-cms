<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Upload delete API (upload/delete)')]
final class ApiUploadDeleteTest extends ApiTestCase
{
    private const UPLOAD = 'api-upload-delete-test.jpg';
    private const CONTENT = 'not really an image, but the extension is what counts';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        $this->watchDir($this->config->mediaDir);

        file_put_contents($this->config->mediaDir . '/' . self::UPLOAD, self::CONTENT);
    }

    public function testUploadIsBackedUpBeforeItIsDeleted(): void
    {
        $response = $this->api('upload/delete', ['fileName' => 'media/' . self::UPLOAD]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist($this->config->mediaDir . '/' . self::UPLOAD);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertStringContainsString('media|api-upload-delete-test.jpg', $backups[0]);
        $this->assertSame(self::CONTENT, file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testUploadWithDisallowedExtensionIsNotDeleted(): void
    {
        $file = $this->config->mediaDir . '/api-upload-delete-test.php';
        file_put_contents($file, '<?php // must survive');

        try {
            $response = $this->api('upload/delete', ['fileName' => 'media/api-upload-delete-test.php']);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertFileExists($file);
        } finally {
            @unlink($file);
        }
    }

    public function testUnknownUploadIsRejected(): void
    {
        $response = $this->api('upload/delete', ['fileName' => 'media/does-not-exist.jpg']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('upload/delete', ['fileName' => 'media/' . self::UPLOAD], 'POST', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
        $this->assertFileExists($this->config->mediaDir . '/' . self::UPLOAD);
    }
}

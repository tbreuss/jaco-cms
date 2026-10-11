<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Upload create API (upload/create)')]
final class ApiUploadCreateTest extends ApiTestCase
{
    private const CONTENT = 'not really an image, but the extension is what counts';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->uploadDir);
    }

    protected function tearDown(): void
    {
        if (isset($_FILES['file']['tmp_name']) && is_string($_FILES['file']['tmp_name'])) {
            @unlink($_FILES['file']['tmp_name']);
        }
        unset($_FILES['file']);

        parent::tearDown();
    }

    public function testUploadIsStored(): void
    {
        $this->fakeUpload('api-upload-create-test.png');

        $response = $this->api('upload/create', method: 'POST');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('api-upload-create-test.png', $body['file']);
        $this->assertSame(self::CONTENT, file_get_contents($this->config->uploadDir . '/api-upload-create-test.png'));
    }

    public function testExistingFileIsNotOverwritten(): void
    {
        file_put_contents($this->config->uploadDir . '/api-upload-create-test.png', 'original');
        $this->fakeUpload('api-upload-create-test.png');

        $response = $this->api('upload/create', method: 'POST');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('api-upload-create-test-1.png', $body['file']);
        $this->assertSame('original', file_get_contents($this->config->uploadDir . '/api-upload-create-test.png'));
        $this->assertSame(self::CONTENT, file_get_contents($this->config->uploadDir . '/api-upload-create-test-1.png'));
    }

    public function testUploadWithDisallowedExtensionIsRejected(): void
    {
        $this->fakeUpload('api-upload-create-test.php');

        $response = $this->api('upload/create', method: 'POST');

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist($this->config->uploadDir . '/api-upload-create-test.php');
    }

    public function testUploadWithSpacesAndDotsInNameIsStored(): void
    {
        $this->fakeUpload('Bildschirmfoto 2026-07-21 um 06.51.53.png');

        $response = $this->api('upload/create', method: 'POST');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('Bildschirmfoto-2026-07-21-um-06-51-53.png', $body['file']);
        $this->assertSame(self::CONTENT, file_get_contents($this->config->uploadDir . '/Bildschirmfoto-2026-07-21-um-06-51-53.png'));
    }

    public function testMissingUploadIsRejected(): void
    {
        $response = $this->api('upload/create', method: 'POST');

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testOversizedUploadIsRejected(): void
    {
        $_FILES['file'] = [
            'name' => 'api-upload-create-test.png',
            'type' => 'image/png',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0,
        ];

        $response = $this->api('upload/create', method: 'POST');

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('The file exceeds the size limit.', $body[0]['detail']);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $this->fakeUpload('api-upload-create-test.png');

        $response = $this->api('upload/create', method: 'POST', authenticated: false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileDoesNotExist($this->config->uploadDir . '/api-upload-create-test.png');
    }

    private function fakeUpload(string $name): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'jaco-upload-test-');
        file_put_contents($tmp, self::CONTENT);

        $_FILES['file'] = [
            'name' => $name,
            'type' => 'image/png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen(self::CONTENT),
        ];
    }
}

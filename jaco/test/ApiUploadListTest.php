<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Upload list API (upload/list)')]
final class ApiUploadListTest extends ApiTestCase
{
    private const UPLOAD = 'upload/api-upload-list.jpg';
    private const TEXT = 'upload/api-upload-list.txt';
    private const HIDDEN = 'api-upload-hidden.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->uploadDir);
        file_put_contents($this->config->webDir . '/' . self::UPLOAD, 'image bytes');
        file_put_contents($this->config->webDir . '/' . self::TEXT, 'not an upload');
    }

    public function testOnlyAllowedFilesOutsideTheAppDirAreListed(): void
    {
        file_put_contents($this->config->appDir . '/' . self::HIDDEN, 'hidden');

        try {
            $response = $this->api('upload/list', method: 'GET');

            $this->assertSame(Status::OK, $response->status);

            $body = (array) json_decode((string) $response->body, true);
            $this->assertSame('', $body['filter']);
            $this->assertSame(['created', 'desc'], $body['sort']);

            $files = array_column($body['items'], 'file');
            $this->assertNotContains(self::TEXT, $files, 'a file without an allowed extension must not be listed');
            $this->assertNotContains($this->config->appDirName . '/' . self::HIDDEN, $files, 'the app dir must stay invisible');

            $item = $this->itemWithFile($body['items'], self::UPLOAD);
            $this->assertSame('jpg', $item['extension']);
            $this->assertSame(strlen('image bytes'), $item['size']);
            $this->assertNull($item['content']);
            $this->assertIsInt($item['created']);
        } finally {
            @unlink($this->config->appDir . '/' . self::HIDDEN);
        }
    }

    public function testFilterNarrowsTheList(): void
    {
        $response = $this->api('upload/list', method: 'GET', query: ['filter' => 'api-upload-list.jpg']);
        $body = (array) json_decode((string) $response->body, true);

        $this->assertSame('api-upload-list.jpg', $body['filter']);
        $this->assertCount(1, $body['items']);
        $this->assertSame(self::UPLOAD, $body['items'][0]['file']);
    }

    public function testInvalidSortIsRejected(): void
    {
        // "name" is not a sort parameter of uploads
        $response = $this->api('upload/list', method: 'GET', query: ['sort' => 'name']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('upload/list', method: 'GET', authenticated: false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

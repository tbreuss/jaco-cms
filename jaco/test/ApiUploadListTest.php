<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Upload list API (upload/list)')]
final class ApiUploadListTest extends ApiTestCase
{
    private const UPLOAD = 'media/api-upload-list.jpg';
    private const TEXT = 'media/api-upload-list.txt';
    private const HIDDEN = 'api-upload-hidden.jpg';
    private const SITE_IMAGE = 'api-upload-site.png';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->mediaDir);
        file_put_contents($this->config->webDir . '/' . self::UPLOAD, 'image bytes');
        file_put_contents($this->config->webDir . '/' . self::TEXT, 'not an upload');
        // an image that lives next to the pages: Jaco makes an existing HTML site
        // editable, so the library has to offer it
        file_put_contents($this->config->webDir . '/' . self::SITE_IMAGE, 'site image');
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
            $this->assertContains(self::SITE_IMAGE, $files, 'an image of the site is listed as well');

            $item = $this->itemWithFile($body['items'], self::UPLOAD);
            $this->assertSame('jpg', $item['extension']);
            $this->assertSame(strlen('image bytes'), $item['size']);
            $this->assertNull($item['content']);
            $this->assertIsInt($item['created']);
        } finally {
            @unlink($this->config->appDir . '/' . self::HIDDEN);
        }
    }

    public function testOperatorDataAndApplicationStayOutOfTheLibrary(): void
    {
        // backups of deleted uploads live under jaco-data: listing them would offer
        // copies that no page references
        @mkdir($this->config->backupDir, 0755, true);
        $backup = $this->config->backupDir . '/20260101_000000_aaaa_media|foto.jpg';
        file_put_contents($backup, 'old image');

        try {
            $body = (array) json_decode((string) $this->api('upload/list', method: 'GET')->body, true);
            $files = array_column($body['items'], 'file');

            $this->assertSame([], array_filter(
                $files,
                static fn(string $file): bool => str_starts_with($file, 'jaco-data/'),
            ), 'nothing below the operator data is an image of the site');
            $this->assertNotContains($this->config->appDirName . '/' . self::HIDDEN, $files);
        } finally {
            @unlink($backup);
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

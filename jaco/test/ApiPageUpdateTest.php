<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_DIR;
use const jaco\WEB_DIR;

/**
 * Integration tests for the page/update API - the path the page manager uses. It owns the
 * whole document: doctype and head are stored as sent, only the body is sanitized.
 *
 * @testdox Page update API (page/update)
 */
final class ApiPageUpdateTest extends ApiTestCase
{
    private const PAGE = 'api-update-test.html';

    /** a 1x1 transparent png */
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private string $originalContent = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        $this->watchDir(BACKUP_DIR);
        $this->watchDir(UPLOAD_DIR);

        $this->originalContent = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Update test</title></head>"
            . "<body><p>before</p></body></html>\n";
        file_put_contents(WEB_DIR . '/' . self::PAGE, $this->originalContent);
        @unlink(dirname(WEB_DIR) . '/api-update-outside.html');
    }

    protected function tearDown(): void
    {
        @unlink(dirname(WEB_DIR) . '/api-update-outside.html');

        parent::tearDown();
    }

    public function testDocumentIsStoredAsSentButTheBodyIsSanitized(): void
    {
        $document = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Client title</title><link rel="stylesheet" href="/style.css">'
            . '<script src="/head.js"></script></head>'
            . '<body><h1>Body</h1><script src="/body.js"></script>'
            . '<p onclick="steal()">text</p></body></html>';

        $response = $this->api('page/update', ['fileName' => self::PAGE, 'html' => $document]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);

        // the page manager owns doctype and head, they end up in the file as sent
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<title>Client title</title>', $content);
        $this->assertStringContainsString('<meta charset="utf-8">', $content);
        $this->assertStringContainsString('href="/style.css"', $content);
        $this->assertStringContainsString('<script src="/head.js"></script>', $content);

        // the body is content and is sanitized
        $this->assertStringContainsString('<h1>Body</h1>', $content);
        $this->assertStringNotContainsString('/body.js', $content);
        $this->assertStringNotContainsString('onclick', $content);
    }

    public function testPageIsWrittenBackedUpAndEmbeddedImageIsExtracted(): void
    {
        $response = $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => $this->pageWithEmbeddedImage(),
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);
        $this->assertStringContainsString('upload/api-update-image', $content);
        $this->assertStringNotContainsString('data:image', $content);
        $this->assertStringNotContainsString('data-jaco-editable-image-file', $content);

        $newBackups = $this->newEntriesIn(BACKUP_DIR);
        $this->assertCount(1, $newBackups, 'exactly one backup is expected');
        $this->assertSame($this->originalContent, file_get_contents(BACKUP_DIR . '/' . $newBackups[0]));

        $newUploads = $this->newEntriesIn(UPLOAD_DIR);
        $this->assertCount(1, $newUploads, 'exactly one extracted image is expected');
        $this->assertStringStartsWith('api-update-image', $newUploads[0]);
        $this->assertSame(
            base64_decode(self::PNG_BASE64, true),
            file_get_contents(UPLOAD_DIR . '/' . $newUploads[0]),
        );
    }

    public function testEditableMarkersSurviveASave(): void
    {
        $response = $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<body><div data-jaco-editable><h1>Block</h1></div>'
                . '<p data-jaco-editable-line>Zeile</p></body>',
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);
        $this->assertStringContainsString('data-jaco-editable>', $content);
        $this->assertStringContainsString('data-jaco-editable-line>', $content);
    }

    public function testPageIsNotTouchedWhenTheRequestHasNoToken(): void
    {
        $response = $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<p>overwritten</p>',
        ], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertSame($this->originalContent, file_get_contents(WEB_DIR . '/' . self::PAGE));
        $this->assertCount(0, $this->newEntriesIn(BACKUP_DIR));
    }

    public function testPageIsNotTouchedWhenTheBodyViolatesTheSchema(): void
    {
        // the schema requires "html"
        $response = $this->api('page/update', ['fileName' => self::PAGE]);

        $this->assertSame(Status::BAD_REQUEST, $response->status);
        $this->assertSame($this->originalContent, file_get_contents(WEB_DIR . '/' . self::PAGE));
        $this->assertCount(0, $this->newEntriesIn(BACKUP_DIR));
    }

    public function testUnknownPageIsRejected(): void
    {
        $response = $this->api('page/update', [
            'fileName' => 'does-not-exist.html',
            'html' => '<p>new</p>',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/does-not-exist.html');
    }

    public function testPageOutsideTheWebDirCannotBeUpdated(): void
    {
        $outsideFile = dirname(WEB_DIR) . '/api-update-outside.html';
        file_put_contents($outsideFile, 'keep me');

        $response = $this->api('page/update', [
            'fileName' => '../api-update-outside.html',
            'html' => '<p>overwritten</p>',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('keep me', file_get_contents($outsideFile));
    }

    public function testPageInTheAppDirCannotBeUpdated(): void
    {
        $file = APP_DIR . '/api-update-protected.html';
        file_put_contents($file, 'admin ui');

        try {
            $response = $this->api('page/update', [
                'fileName' => APP_DIR_NAME . '/api-update-protected.html',
                'html' => '<p>overwritten</p>',
            ]);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertSame('admin ui', file_get_contents($file));
        } finally {
            @unlink($file);
        }
    }

    private function pageWithEmbeddedImage(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><title>Update test</title></head><body>'
            . '<img data-jaco-editable-image src="data:image/png;base64,' . self::PNG_BASE64 . '"'
            . ' data-jaco-editable-image-file="api-update-image.png" alt="test">'
            . '</body></html>';
    }
}

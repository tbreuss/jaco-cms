<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\UPLOAD_DIR;
use const jaco\WEB_DIR;

/**
 * Integration tests for the page/update API: they run through the real route table,
 * the authentication check, the JSON schema validation and the domain functions.
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
        @unlink(dirname(WEB_DIR) . '/api-update-outside.html');

        $this->originalContent = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Update test</title></head>"
            . "<body><p>before</p></body></html>\n";
        file_put_contents(WEB_DIR . '/' . self::PAGE, $this->originalContent);
    }

    protected function tearDown(): void
    {
        @unlink(dirname(WEB_DIR) . '/api-update-outside.html');

        parent::tearDown();
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

    public function testStoredDocumentHeadSurvivesASaveAndTheClientHeadIsIgnored(): void
    {
        $clientDocument = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Injected title</title><script src="https://evil.example/x.js"></script></head>'
            . '<body><p>from the client</p></body></html>';

        $response = $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => $clientDocument,
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);

        // the stored document stays a document
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<title>Update test</title>', $content);

        // the head of the request is not taken over
        $this->assertStringNotContainsString('Injected title', $content);
        $this->assertStringNotContainsString('evil.example', $content);

        // the body of the request is
        $this->assertStringContainsString('<p>from the client</p>', $content);
        $this->assertStringNotContainsString('<p>before</p>', $content);

        // the title is still there for the api
        $view = $this->api('page/view', method: 'GET', query: ['file' => self::PAGE]);
        $body = (array) json_decode((string) $view->body, true);
        $this->assertSame('Update test', $body['title']);
    }

    public function testTitleCanBeChangedWithoutTouchingTheHead(): void
    {
        $response = $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<html lang="en"><head><title>Whatever the client sends</title></head>'
                . '<body><p>new body</p></body></html>',
            'pageTitle' => 'New title',
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);
        $this->assertStringContainsString('<title>New title</title>', $content);
        $this->assertStringNotContainsString('Whatever the client sends', $content);
        $this->assertStringNotContainsString('Update test', $content);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<p>new body</p>', $content);
    }

    public function testTitleIsEscaped(): void
    {
        $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<body><p>new</p></body>',
            'pageTitle' => '<script>alert(1)</script>',
        ]);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);
        $this->assertStringContainsString(
            '<title>&lt;script&gt;alert(1)&lt;/script&gt;</title>',
            $content,
        );
        $this->assertStringNotContainsString('<script>', $content);
    }

    public function testMissingOrEmptyTitleLeavesTheStoredTitleUntouched(): void
    {
        $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<body><p>new</p></body>',
        ]);
        $this->assertStringContainsString(
            '<title>Update test</title>',
            (string) file_get_contents(WEB_DIR . '/' . self::PAGE),
        );

        $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<body><p>new</p></body>',
            'pageTitle' => '',
        ]);
        $this->assertStringContainsString(
            '<title>Update test</title>',
            (string) file_get_contents(WEB_DIR . '/' . self::PAGE),
        );
    }

    public function testTitleOfAWrongTypeIsRejected(): void
    {
        $response = $this->api('page/update', [
            'fileName' => self::PAGE,
            'html' => '<body><p>new</p></body>',
            'pageTitle' => 123,
        ]);

        $this->assertSame(Status::BAD_REQUEST, $response->status);
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

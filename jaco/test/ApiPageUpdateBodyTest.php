<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;

/**
 * Integration tests for the page/update-body API - the path the inline editor uses.
 * It sends content, never a document: the stored document keeps doctype and head.
 *
 * @testdox Page update body API (page/update-body)
 */
final class ApiPageUpdateBodyTest extends ApiTestCase
{
    private const PAGE = 'api-update-body.html';
    private const FRAGMENT = 'api-update-body-fragment.html';

    private const DOCUMENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><base href=\"/\">"
        . "<meta charset=\"utf-8\"><title>Body test</title><link rel=\"stylesheet\" href=\"/style.css\">"
        . "</head>\n<body><div data-jaco-editable><p>alt</p></div></body>\n</html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        $this->watchDir(BACKUP_DIR);
        file_put_contents(WEB_DIR . '/' . self::PAGE, self::DOCUMENT);
    }

    public function testBodyIsReplacedAndTheStoredDocumentStaysUntouched(): void
    {
        $response = $this->api('page/update-body', [
            'fileName' => self::PAGE,
            'body' => '<div data-jaco-editable><p>neu</p></div>',
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);

        // the document around the body is the stored one
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<title>Body test</title>', $content);
        $this->assertStringContainsString('<base href="/">', $content);
        $this->assertStringContainsString('href="/style.css"', $content);

        // the body is the new one, markers included
        $this->assertStringContainsString('<div data-jaco-editable><p>neu</p></div>', $content);
        $this->assertStringNotContainsString('<p>alt</p>', $content);
    }

    public function testBodyIsSanitizedAndTheEditorsOwnHeadCannotGetIn(): void
    {
        $response = $this->api('page/update-body', [
            'fileName' => self::PAGE,
            'body' => '<div data-jaco-editable><p onclick="steal()">text</p></div>'
                . '<script src="https://evil.example/x.js"></script>'
                . '<link rel="stylesheet" href="https://evil.example/x.css">'
                . '<title>Injected</title>',
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);
        $this->assertStringContainsString('<p>text</p>', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('evil.example', $content);
        $this->assertStringNotContainsString('<script', $content);

        // the head of the stored document stays the one from disk: a title from the
        // request is unwrapped into body text, it cannot become the document title
        $this->assertStringNotContainsString('<title>Injected</title>', $content);
        $this->assertStringContainsString('<title>Body test</title>', $content);
    }

    public function testFragmentPageStaysAFragment(): void
    {
        file_put_contents(WEB_DIR . '/' . self::FRAGMENT, '<p>alt</p>');

        $response = $this->api('page/update-body', [
            'fileName' => self::FRAGMENT,
            'body' => '<p>neu</p>',
        ]);

        $this->assertSame(Status::CREATED, $response->status);
        $this->assertSame('<p>neu</p>', file_get_contents(WEB_DIR . '/' . self::FRAGMENT));
    }

    public function testStoredDocumentIsBackedUpBeforeTheWrite(): void
    {
        $this->api('page/update-body', ['fileName' => self::PAGE, 'body' => '<p>neu</p>']);

        $newBackups = $this->newEntriesIn(BACKUP_DIR);
        $this->assertCount(1, $newBackups);
        $this->assertSame(self::DOCUMENT, file_get_contents(BACKUP_DIR . '/' . $newBackups[0]));
    }

    public function testEmptyBodyIsRejected(): void
    {
        $response = $this->api('page/update-body', ['fileName' => self::PAGE, 'body' => '']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame(self::DOCUMENT, file_get_contents(WEB_DIR . '/' . self::PAGE));
    }

    public function testMissingBodyIsRejected(): void
    {
        $response = $this->api('page/update-body', ['fileName' => self::PAGE]);

        $this->assertSame(Status::BAD_REQUEST, $response->status);
        $this->assertSame(self::DOCUMENT, file_get_contents(WEB_DIR . '/' . self::PAGE));
    }

    public function testPageInTheAppDirIsRejected(): void
    {
        $file = APP_DIR . '/api-update-body-protected.html';
        file_put_contents($file, 'admin ui');

        try {
            $response = $this->api('page/update-body', [
                'fileName' => APP_DIR_NAME . '/api-update-body-protected.html',
                'body' => '<p>overwritten</p>',
            ]);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertSame('admin ui', file_get_contents($file));
        } finally {
            @unlink($file);
        }
    }

    public function testUnknownPageIsRejected(): void
    {
        $response = $this->api('page/update-body', ['fileName' => 'does-not-exist.html', 'body' => '<p>x</p>']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/update-body', [
            'fileName' => self::PAGE,
            'body' => '<p>overwritten</p>',
        ], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertSame(self::DOCUMENT, file_get_contents(WEB_DIR . '/' . self::PAGE));
    }
}

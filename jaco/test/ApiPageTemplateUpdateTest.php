<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page template update API (template/page/update)')]
final class ApiPageTemplateUpdateTest extends ApiTestCase
{
    private const TEMPLATE = 'api-page-template.html';

    private const DOCUMENT = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <title>[EN] Blank</title>\n  <script src=\"assets/theme.js\"></script>\n</head>\n<body>\n  <h1 data-jaco-line>Title</h1>\n</body>\n</html>\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->watchDir($this->config->templatePageDir);
        $this->watchDir($this->config->backupDir);

        @mkdir($this->config->templatePageDir, 0755, true);
        file_put_contents($this->config->templatePageDir . '/' . self::TEMPLATE, self::DOCUMENT);
    }

    public function testTheDocumentIsWrittenUnchanged(): void
    {
        $updated = self::DOCUMENT . "<!-- edited -->\n";

        $response = $this->api('template/page/update', [
            'fileName' => self::TEMPLATE,
            'content' => $updated,
        ]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::TEMPLATE, $body['file']);
        $this->assertSame('[EN] Blank', $body['title']);
        $this->assertSame($updated, $body['content']);
        $this->assertIsInt($body['created']);

        // a page template is a whole document: the script and the doctype stay,
        // the sanitizer a page save runs would drop them
        $stored = (string) file_get_contents($this->config->templatePageDir . '/' . self::TEMPLATE);
        $this->assertSame($updated, $stored);
        $this->assertStringContainsString('<script src="assets/theme.js"></script>', $stored);
        $this->assertStringStartsWith('<!DOCTYPE html>', $stored);
    }

    public function testTheFileIsBackedUpBeforeItChanges(): void
    {
        $response = $this->api('template/page/update', [
            'fileName' => self::TEMPLATE,
            'content' => str_replace('[EN] Blank', '[EN] Edited', self::DOCUMENT),
        ]);

        $this->assertSame(Status::OK, $response->status);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'the template is backed up before it is written');
        $this->assertStringContainsString(self::TEMPLATE, (string) $backups[0]);
        $this->assertSame(self::DOCUMENT, (string) file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testAFragmentIsRejectedAndNothingIsWritten(): void
    {
        $response = $this->api('template/page/update', [
            'fileName' => self::TEMPLATE,
            'content' => '<h1>No head</h1>',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('content', (string) $response->body);
        $this->assertSame(self::DOCUMENT, (string) file_get_contents($this->config->templatePageDir . '/' . self::TEMPLATE));
        $this->assertSame([], $this->newEntriesIn($this->config->backupDir), 'a rejected edit is not backed up');
    }

    public function testADocumentWithoutTitleIsRejected(): void
    {
        // the page manager fills the title of a new page, so it has to be there
        $response = $this->api('template/page/update', [
            'fileName' => self::TEMPLATE,
            'content' => "<!DOCTYPE html>\n<html><head></head><body><h1>x</h1></body></html>\n",
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('content', (string) $response->body);
    }

    public function testAMissingTemplateIsRejected(): void
    {
        // creating stays on the file system: the listing is the way it was
        $response = $this->api('template/page/update', [
            'fileName' => 'does-not-exist.html',
            'content' => self::DOCUMENT,
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFalse(is_file($this->config->templatePageDir . '/does-not-exist.html'));
    }

    public function testParentSegmentsAreRejected(): void
    {
        $response = $this->api('template/page/update', [
            'fileName' => '../settings.html',
            'content' => self::DOCUMENT,
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testTheDocumentCanBeRead(): void
    {
        // the listing carries the title only, so the editor asks for one file
        $response = $this->api('template/page/view', method: 'GET', query: ['file' => self::TEMPLATE]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::TEMPLATE, $body['file']);
        $this->assertSame('[EN] Blank', $body['title']);
        $this->assertSame(self::DOCUMENT, $body['content']);
        $this->assertIsInt($body['created']);
    }

    public function testAnEditorMayReadTheDocument(): void
    {
        // page/create reads the same file to create a page from it, so an editor
        // already reaches the content - the view route only makes it visible
        $config = test_config();
        $token = \jaco\core\auth\create_token($config, TEST_USERNAME, \jaco\core\auth\ROLE_EDITOR, '', false);

        $response = $this->api(
            'template/page/view',
            [],
            'GET',
            false,
            query: ['file' => self::TEMPLATE],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertSame(Status::OK, $response->status);
    }

    public function testReadingAMissingTemplateIsRejected(): void
    {
        $response = $this->api('template/page/view', method: 'GET', query: ['file' => 'does-not-exist.html']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testTheEditorMayNotChangeAPageTemplate(): void
    {
        $config = test_config();
        $token = \jaco\core\auth\create_token($config, TEST_USERNAME, \jaco\core\auth\ROLE_EDITOR, '', false);

        $response = $this->api(
            'template/page/update',
            ['fileName' => self::TEMPLATE, 'content' => self::DOCUMENT],
            'POST',
            false,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }

    public function testARequestWithoutTokenIsUnauthorized(): void
    {
        $response = $this->api('template/page/update', [], 'POST', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

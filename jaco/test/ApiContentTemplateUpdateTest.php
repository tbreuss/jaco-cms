<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Content template update API (template/content/update)')]
final class ApiContentTemplateUpdateTest extends ApiTestCase
{
    private const TEMPLATE = 'api-content-template.html';

    private const FRAGMENT = "<div class=\"feature\">\n  <h3 data-jaco-line>New card</h3>\n</div>\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->watchDir($this->config->templateContentDir);
        $this->watchDir($this->config->backupDir);

        @mkdir($this->config->templateContentDir, 0755, true);
        file_put_contents($this->config->templateContentDir . '/' . self::TEMPLATE, self::FRAGMENT);
    }

    public function testTheFragmentIsWrittenUnchanged(): void
    {
        $updated = str_replace('New card', 'Edited card', self::FRAGMENT);

        $response = $this->api('template/content/update', [
            'fileName' => self::TEMPLATE,
            'content' => $updated,
        ]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame(self::TEMPLATE, $body['file']);
        $this->assertSame(self::TEMPLATE, $body['title'], 'a fragment has no title, the file name stands in');
        $this->assertSame($updated, $body['content']);
        $this->assertSame($updated, (string) file_get_contents($this->config->templateContentDir . '/' . self::TEMPLATE));
    }

    public function testTheFileIsBackedUpBeforeItChanges(): void
    {
        $response = $this->api('template/content/update', [
            'fileName' => self::TEMPLATE,
            'content' => str_replace('New card', 'Edited card', self::FRAGMENT),
        ]);

        $this->assertSame(Status::OK, $response->status);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'the fragment is backed up before it is written');
        $this->assertSame(self::FRAGMENT, (string) file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testTwoRootsAreRejectedAndNothingIsWritten(): void
    {
        // a fragment is inserted as one block: two roots would become two blocks
        $response = $this->api('template/content/update', [
            'fileName' => self::TEMPLATE,
            'content' => '<div>one</div><div>two</div>',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('content', (string) $response->body);
        $this->assertSame(self::FRAGMENT, (string) file_get_contents($this->config->templateContentDir . '/' . self::TEMPLATE));
        $this->assertSame([], $this->newEntriesIn($this->config->backupDir), 'a rejected edit is not backed up');
    }

    public function testTextBesideTheRootIsRejected(): void
    {
        $response = $this->api('template/content/update', [
            'fileName' => self::TEMPLATE,
            'content' => "stray text<div>one</div>",
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testAnUnknownDataAttributeNameIsRejected(): void
    {
        // the file is written unsanitized, so the contract check stands in for
        // the sanitizer: an unknown data-jaco name would never be editable
        $response = $this->api('template/content/update', [
            'fileName' => self::TEMPLATE,
            'content' => '<div data-jaco-line data-jaco-typo>x</div>',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('data-jaco-typo', (string) $response->body);
    }

    public function testAMissingTemplateIsRejected(): void
    {
        $response = $this->api('template/content/update', [
            'fileName' => 'does-not-exist.html',
            'content' => self::FRAGMENT,
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFalse(is_file($this->config->templateContentDir . '/does-not-exist.html'));
    }

    public function testParentSegmentsAreRejected(): void
    {
        $response = $this->api('template/content/update', [
            'fileName' => '../site/footer.html',
            'content' => self::FRAGMENT,
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testTheEditorMayNotChangeAContentTemplate(): void
    {
        $config = test_config();
        $token = \jaco\core\auth\create_token($config, TEST_USERNAME, \jaco\core\auth\ROLE_EDITOR, '', false);

        $response = $this->api(
            'template/content/update',
            ['fileName' => self::TEMPLATE, 'content' => self::FRAGMENT],
            'POST',
            false,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }

    public function testARequestWithoutTokenIsUnauthorized(): void
    {
        $response = $this->api('template/content/update', [], 'POST', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

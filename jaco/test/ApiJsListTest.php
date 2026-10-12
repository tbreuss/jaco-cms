<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Script list API (js/list)')]
final class ApiJsListTest extends ApiTestCase
{
    private const FILE = 'api-js-list-test.js';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::FILE, "console.log('x');\n");
    }

    public function testScriptListContainsOnlyJsFiles(): void
    {
        file_put_contents($this->config->webDir . '/api-js-list-test.html', '<p>not a script</p>');

        $response = $this->api('js/list', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $files = array_column($body, 'file');
        $this->assertContains(self::FILE, $files);
        $this->assertNotContains('api-js-list-test.html', $files);

        foreach ($files as $entry) {
            $this->assertStringEndsWith('.js', $entry);
        }
    }

    public function testBackupScriptsAreNotListed(): void
    {
        file_put_contents($this->config->backupDir . '/api-js-list-backup.js', "console.log('x');\n");

        $response = $this->api('js/list', method: 'GET');

        $body = (array) json_decode((string) $response->body, true);
        $files = array_column($body, 'file');
        $this->assertNotContains('api-js-list-backup.js', $files);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('js/list', method: 'GET', authenticated: false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;

/**
 * @testdox Stylesheet list API (css/list)
 */
final class ApiCssListTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        file_put_contents(WEB_DIR . '/api-css-list-test.css', "h1 {\n    color: red;\n}\n");
        file_put_contents(WEB_DIR . '/api-css-list-test.html', '<p>not a stylesheet</p>');
    }

    public function testListContainsStylesheetsOnly(): void
    {
        $response = $this->api('css/list', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $item = $this->itemWithFile($body, 'api-css-list-test.css');
        $this->assertIsInt($item['created']);

        foreach ($body as $entry) {
            $this->assertStringEndsWith('.css', $entry['file']);
        }
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('css/list', method: 'GET', authenticated: false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }

    public function testBackupStylesheetsAreExcluded(): void
    {
        $this->watchDir(BACKUP_DIR);
        file_put_contents(BACKUP_DIR . '/api-css-list-backup.css', "h1 {\n    color: red;\n}\n");

        $response = $this->api('css/list', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        foreach ($body as $entry) {
            $this->assertStringNotContainsString('api-css-list-backup.css', $entry['file']);
        }
    }
}

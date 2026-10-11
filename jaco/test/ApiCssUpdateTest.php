<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;

/**
 * @testdox Stylesheet update API (css/update)
 */
final class ApiCssUpdateTest extends ApiTestCase
{
    private const FILE = 'api-css-update-test.css';
    private const ORIGINAL = "h1 {\n    color: red;\n}\n";
    private const CHANGED = "h1 {\n    color: blue;\n}\np {\n    margin: 0;\n}\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir(WEB_DIR);
        $this->watchDir(BACKUP_DIR);
        file_put_contents(WEB_DIR . '/' . self::FILE, self::ORIGINAL);
    }

    public function testStylesheetIsBackedUpBeforeItIsUpdated(): void
    {
        $response = $this->api('css/update', ['fileName' => self::FILE, 'css' => self::CHANGED]);

        $this->assertSame(Status::CREATED, $response->status);
        $this->assertSame(self::CHANGED, file_get_contents(WEB_DIR . '/' . self::FILE));

        $backups = $this->newEntriesIn(BACKUP_DIR);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertSame(self::ORIGINAL, file_get_contents(BACKUP_DIR . '/' . $backups[0]));
    }

    public function testEmptyStylesheetIsRejected(): void
    {
        $response = $this->api('css/update', ['fileName' => self::FILE, 'css' => '']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents(WEB_DIR . '/' . self::FILE));
    }

    public function testUnknownStylesheetIsRejected(): void
    {
        $response = $this->api('css/update', ['fileName' => 'does-not-exist.css', 'css' => self::CHANGED]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('css/update', ['fileName' => self::FILE, 'css' => self::CHANGED], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents(WEB_DIR . '/' . self::FILE));
    }
}

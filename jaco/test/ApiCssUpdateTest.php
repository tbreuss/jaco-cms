<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

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

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::FILE, self::ORIGINAL);
    }

    public function testStylesheetIsBackedUpBeforeItIsUpdated(): void
    {
        $response = $this->api('css/update', ['fileName' => self::FILE, 'css' => self::CHANGED]);

        $this->assertSame(Status::CREATED, $response->status);
        $this->assertSame(self::CHANGED, file_get_contents($this->config->webDir . '/' . self::FILE));

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'exactly one backup is expected');
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testEmptyStylesheetIsRejected(): void
    {
        $response = $this->api('css/update', ['fileName' => self::FILE, 'css' => '']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::FILE));
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
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::FILE));
    }
}

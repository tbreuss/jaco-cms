<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Script update API (js/update)')]
final class ApiJsUpdateTest extends ApiTestCase
{
    private const FILE = 'api-js-update-test.js';
    private const ORIGINAL = "console.log('before');\n";
    private const CHANGED = "console.log('after');\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::FILE, self::ORIGINAL);
    }

    public function testScriptContentIsUpdatedAndBackedUp(): void
    {
        $response = $this->api('js/update', ['fileName' => self::FILE, 'js' => self::CHANGED]);

        $this->assertSame(Status::CREATED, $response->status);
        $this->assertSame(self::CHANGED, file_get_contents($this->config->webDir . '/' . self::FILE));

        // the original content was backed up before the write
        $backups = glob($this->config->backupDir . '/*' . self::FILE);
        $this->assertCount(1, $backups);
        $this->assertSame(self::ORIGINAL, file_get_contents($backups[0]));
    }

    public function testEmptyContentIsRejected(): void
    {
        $response = $this->api('js/update', ['fileName' => self::FILE, 'js' => '']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testMissingScriptIsRejected(): void
    {
        $response = $this->api('js/update', ['fileName' => 'does-not-exist.js', 'js' => self::CHANGED]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testNonJsFileIsRejected(): void
    {
        $response = $this->api('js/update', ['fileName' => 'index.html', 'js' => self::CHANGED]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('js/update', ['fileName' => self::FILE, 'js' => self::CHANGED], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

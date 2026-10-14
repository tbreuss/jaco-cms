<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('System log clear API (system/log/clear)')]
final class ApiSystemLogClearTest extends ApiTestCase
{
    private const LINE = "[14-Oct-2026 06:59:56 CEST] PHP Warning: clear-test\n";

    protected function tearDown(): void
    {
        @unlink($this->config->phpErrorLog);

        parent::tearDown();
    }

    public function testClearEmptiesTheLog(): void
    {
        file_put_contents($this->config->phpErrorLog, self::LINE . self::LINE);

        $response = $this->api('system/log/clear', []);

        $this->assertSame(Status::OK, $response->status);
        $this->assertSame('', file_get_contents($this->config->phpErrorLog));
    }

    public function testClearWithoutLogFileSucceeds(): void
    {
        @unlink($this->config->phpErrorLog);

        $response = $this->api('system/log/clear', []);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist($this->config->phpErrorLog);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('system/log/clear', [], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

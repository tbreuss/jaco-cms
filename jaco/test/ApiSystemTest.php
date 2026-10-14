<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('System environment API (system)')]
final class ApiSystemTest extends ApiTestCase
{
    public function testEnvironmentHasChecksConfigAndOverview(): void
    {
        $response = $this->api('system', [], 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);

        $this->assertArrayHasKey('checks', $body);
        $this->assertNotEmpty($body['checks']);
        foreach ($body['checks'] as $check) {
            $this->assertArrayHasKey('label', $check);
            $this->assertArrayHasKey('ok', $check);
            $this->assertArrayHasKey('detail', $check);
        }

        $this->assertArrayHasKey('config', $body);
        $this->assertArrayHasKey('overview', $body);

        foreach (['pages', 'uploads', 'backups', 'projectSize', 'logSize', 'logLines'] as $key) {
            $this->assertArrayHasKey($key, $body['overview']);
            $this->assertIsInt($body['overview'][$key]);
        }
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('system', [], 'GET', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }
}

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

    public function testNoCheckPrintsAnAbsolutePath(): void
    {
        // the owner's account name and the layout of the hoster are none of the
        // admin's business: the system page shows paths relative to the web root
        $response = $this->api('system', [], 'GET');
        $checks = json_decode((string) $response->body, true)['checks'];

        $absolute = [];
        foreach ($checks as $check) {
            if (\str_contains((string) $check['detail'], $this->config->webDir)) {
                $absolute[] = $check['label'] . ': ' . $check['detail'];
            }
        }

        $this->assertSame([], $absolute, "absolute paths on the system page:\n  " . \implode("\n  ", $absolute));
    }

    public function testTheDetailsNamePathsRelativeToTheWebRoot(): void
    {
        $response = $this->api('system', [], 'GET');
        $checks = json_decode((string) $response->body, true)['checks'];

        $details = [];
        foreach ($checks as $check) {
            $details[$check['label']] = $check['detail'];
        }

        $this->assertSame('jaco/vendor/autoload.php', $details['Composer dependencies'] ?? null);
        $this->assertSame('jaco/.htaccess', $details['App protection (.htaccess)'] ?? null);
        $this->assertSame('jaco-data/.htaccess', $details['Data protection (.htaccess)'] ?? null);
        $this->assertSame('jaco/install.php', $details['Installer removed'] ?? null);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('system', [], 'GET', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

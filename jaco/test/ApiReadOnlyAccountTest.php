<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Status;
use jaco\core\router;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * A read-only account is a real admin that may not store anything: it logs in,
 * sees the whole admin and gets a 403 from every writing route. The read routes
 * and the preview stay available, otherwise there would be nothing to show.
 */
#[TestDox('Read-only account (admin that may not store)')]
final class ApiReadOnlyAccountTest extends ApiTestCase
{
    private function readOnlyConfig(): Config
    {
        return test_config_with([
            'users' => [
                self::READ_ONLY_USER => [
                    'password' => test_config_password_hash(),
                    'role' => auth\ROLE_ADMIN,
                    'readOnly' => true,
                ],
                TEST_USERNAME => [
                    'password' => test_config_password_hash(),
                    'role' => auth\ROLE_ADMIN,
                ],
            ],
        ]);
    }

    private function readOnlyToken(): string
    {
        return auth\create_token($this->readOnlyConfig(), self::READ_ONLY_USER, auth\ROLE_ADMIN, '', true);
    }

    public function testTheTokenCarriesTheReadOnlyClaim(): void
    {
        $claims = auth\read_token($this->readOnlyConfig(), $this->readOnlyToken());

        $this->assertNotNull($claims);
        $this->assertTrue($claims['readOnly']);
        $this->assertSame(auth\ROLE_ADMIN, $claims['role']);
    }

    public function testTheRecordDecidesNotTheToken(): void
    {
        // a token issued before the flag was set must not grant writes
        $token = auth\create_token($this->readOnlyConfig(), self::READ_ONLY_USER, auth\ROLE_ADMIN, '', false);

        $claims = auth\read_token($this->readOnlyConfig(), $token);

        $this->assertNotNull($claims);
        $this->assertTrue($claims['readOnly'], 'the current record wins over the claim');
    }

    public function testEveryWritingRouteIsRefused(): void
    {
        $checked = [];

        foreach (router\routes() as $route) {
            if ($route['method'] !== 'POST' || \in_array($route['action'], router\READ_ONLY_EXCEPTIONS, true)) {
                continue;
            }
            // unauthenticated routes never reach the gate by design
            if ($route['authenticated'] === false) {
                continue;
            }

            $checked[] = $route['action'];

            $response = $this->api(
                $route['action'],
                [],
                'POST',
                false,
                server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->readOnlyToken()],
                config: $this->readOnlyConfig(),
            );

            $this->assertSame(Status::FORBIDDEN, $response->status, $route['action'] . ' must be refused');

            $body = (array) json_decode((string) $response->body, true);
            $this->assertSame('Saving is disabled for this account', $body[0]['title']);
        }

        $this->assertContains('page/update', $checked);
        $this->assertContains('css/update', $checked);
        $this->assertContains('backup/restore', $checked);
    }

    public function testReadingRoutesStayAvailable(): void
    {
        $response = $this->api(
            'page/list',
            [],
            'GET',
            false,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->readOnlyToken()],
            config: $this->readOnlyConfig(),
        );

        $this->assertSame(Status::OK, $response->status);
    }

    public function testAdminRoutesStayAvailableSoTheInterfaceCanBeShown(): void
    {
        // system and css are admin routes: a read-only account has the role, only
        // writing is blocked; system/log and system/php-info are the exception
        foreach (['system', 'css/list', 'template/site/list'] as $action) {
            $response = $this->api(
                $action,
                [],
                'GET',
                false,
                server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->readOnlyToken()],
                config: $this->readOnlyConfig(),
            );

            $this->assertSame(Status::OK, $response->status, $action . ' must stay readable for a read-only account');
        }
    }

    public function testTheServerPagesAreClosed(): void
    {
        // phpinfo() and the raw error log would show paths, versions and possibly
        // environment variables to every visitor of a public demo
        foreach (router\READ_ONLY_DENIED as $action) {
            $response = $this->api(
                $action,
                [],
                'GET',
                false,
                server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->readOnlyToken()],
                config: $this->readOnlyConfig(),
            );

            $this->assertSame(Status::FORBIDDEN, $response->status, $action . ' must stay closed');

            $body = (array) json_decode((string) $response->body, true);
            $this->assertSame('Not available in the demo', $body[0]['title']);
        }
    }

    public function testTheServerPagesStayOpenForAnAdmin(): void
    {
        // the restriction is the read-only flag, not the role. Only the log is
        // asserted here: phpinfo() writes into the output buffer of the test run
        // and cannot be captured reliably.
        $response = $this->api('system/log', [], 'GET', true, config: $this->readOnlyConfig());

        $this->assertSame(Status::OK, $response->status, 'the log stays open for a regular admin');
    }

    public function testThePreviewIsStillAllowed(): void
    {
        // the preview only counts matches and is a POST: a read-only visitor must be
        // able to see what "apply to all" would do
        $response = $this->api(
            'template/site/preview',
            ['fileName' => 'footer.html'],
            'POST',
            false,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->readOnlyToken()],
            config: $this->readOnlyConfig(),
        );

        $this->assertSame(Status::OK, $response->status);
    }

    public function testARegularAdminIsNotBlocked(): void
    {
        $response = $this->api('page/list', [], 'GET', true, config: $this->readOnlyConfig());

        $this->assertSame(Status::OK, $response->status);
    }

    private const READ_ONLY_USER = 'read_only_user';
}

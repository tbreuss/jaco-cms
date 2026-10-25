<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Map;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\core\router;

/**
 * The role matrix: admin may everything, editor may write content, viewer may read,
 * and an unknown role falls back to viewer instead of guessing.
 */
#[TestDox('Roles over the api')]
final class ApiRolesTest extends ApiTestCase
{
    private const PASSWORD = 'role_pass';

    private string $dir;

    private string $digest;

    /** @var array<string, string> */
    private array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/jaco-roles-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0755, true);

        $this->digest = (string) password_hash(self::PASSWORD, PASSWORD_DEFAULT, ['cost' => 4]);

        // the settings file mirrors the config, so a password change has a block to edit
        file_put_contents($this->dir . '/settings.php', <<<PHP
            <?php declare(strict_types=1);

            // keep me
            return [
                'users' => [
                    'boss' => ['password' => '{$this->digest}', 'role' => 'admin'],
                    'ed' => ['password' => '{$this->digest}', 'role' => 'editor'],
                    'mystery' => ['password' => '{$this->digest}', 'role' => 'boss'],
                ],
            ];
            PHP);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/settings.php');
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function config(): Config
    {
        return test_config_with([
            'appDir' => $this->dir,
            'users' => [
                'boss' => ['password' => $this->digest, 'role' => auth\ROLE_ADMIN],
                'ed' => ['password' => $this->digest, 'role' => auth\ROLE_EDITOR],
                'mystery' => ['password' => $this->digest, 'role' => 'boss'],
            ],
        ]);
    }

    private function tokenFor(Config $config, string $user): string
    {
        $this->tokens[$user] ??= (string) auth\authenticate($config, $user, self::PASSWORD);

        return $this->tokens[$user];
    }

    /** @param array<string, mixed> $post */
    private function call(Config $config, string $user, string $action, array $post = [], string $method = 'POST'): Response
    {
        return router\dispatch(new Request(
            query: new Map(['action' => $action]),
            post: new Map($post),
            server: new Map([
                'REQUEST_METHOD' => $method,
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->tokenFor($config, $user),
            ]),
            headers: new Map([]),
        ), $config);
    }

    public function testTheAdminReachesTheSystemRoutes(): void
    {
        $response = $this->call($this->config(), 'boss', 'system/log', [], 'GET');

        $this->assertSame(Status::OK, $response->status);
    }

    public function testTheEditorReadsAndWritesContentButNotCss(): void
    {
        $config = $this->config();

        $this->assertSame(Status::OK, $this->call($config, 'ed', 'page/list', [], 'GET')->status);
        $this->assertSame(Status::FORBIDDEN, $this->call($config, 'ed', 'css/update', ['fileName' => 'x'])->status);
    }

    public function testTheEditorMayNotReachTheAdminRoutes(): void
    {
        $config = $this->config();

        // the editor owns content; design, backups and the system pages are admin
        foreach (['css/list', 'backup/list', 'system'] as $route) {
            $this->assertSame(Status::FORBIDDEN, $this->call($config, 'ed', $route, [], 'GET')->status, $route);
        }
    }

    public function testTheEditorReadsAndAppliesSiteTemplatesButMayNotChangeThem(): void
    {
        // the markup belongs to the admin, rolling it out is the editor's job
        $config = $this->config();

        $this->assertSame(
            Status::OK,
            $this->call($config, 'ed', 'template/site/list', [], 'GET')->status,
            'the editor sees which templates exist',
        );

        $this->assertSame(
            Status::FORBIDDEN,
            $this->call($config, 'ed', 'template/site/update', ['fileName' => 'x.html', 'content' => '<p>x</p>'])->status,
            'the editor may not change the markup',
        );
    }

    public function testTheEditorMayChangeTheirOwnPassword(): void
    {
        $response = $this->call($this->config(), 'ed', 'system/change-password', [
            'withPassword' => self::PASSWORD,
            'withNewPassword' => 'editor-new-pass',
        ]);

        $this->assertSame(Status::OK, $response->status);
    }

    public function testAnAccountWithAnUnknownRoleCannotLogIn(): void
    {
        $config = $this->config();

        // fail closed: a typo in settings.php must not hand out the least privilege
        $this->assertNull(auth\authenticate($config, 'mystery', self::PASSWORD));
    }

    public function testAnInvalidTokenIsUnauthorizedNotForbidden(): void
    {
        // 401 sends the frontend back to the login, 403 only refuses one action: mixing
        // them up logged a viewer out of a perfectly valid session
        $response = router\dispatch(new Request(
            query: new Map(['action' => 'page/list']),
            post: new Map([]),
            server: new Map(['REQUEST_METHOD' => 'GET', 'HTTP_AUTHORIZATION' => 'Bearer nope']),
            headers: new Map([]),
        ), $this->config());

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    public function testTheTokenCarriesTheRole(): void
    {
        $config = $this->config();

        $claims = auth\read_token($config, $this->tokenFor($config, 'ed'));

        $this->assertNotNull($claims);
        $this->assertSame('ed', $claims['user']);
        $this->assertSame(auth\ROLE_EDITOR, $claims['role']);
    }

    public function testAnOldTokenWithoutRoleOnlyReads(): void
    {
        $config = $this->config();

        // a token for a user that exists but was issued before roles existed
        $claims = auth\read_token($config, $this->tokenFor($config, 'ed'));

        $this->assertNotNull($claims);
        $this->assertFalse(auth\role_allows(auth\ROLE_EDITOR, auth\ROLE_ADMIN));
    }
}

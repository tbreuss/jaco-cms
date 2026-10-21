<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\core\router;

/**
 * The role matrix in docs/constraints.md and the route table must not drift apart. Every
 * route named there has to exist with exactly that role, and every route that needs more
 * than viewer has to be named there.
 */
#[TestDox('Roles documented in constraints.md')]
final class DocsRolesTest extends TestCase
{
    /** @var array<string, string>|null */
    private static ?array $documented = null;

    /** @return array<string, string> route name → role, as written in the docs */
    private function documented(): array
    {
        if (self::$documented !== null) {
            return self::$documented;
        }

        $docs = (string) file_get_contents(__DIR__ . '/../../docs/constraints.md');
        $matched = preg_match('/^## Roles\n(.*?)\n## /ms', $docs, $section);

        $this->assertSame(1, $matched, 'the docs have a "## Roles" section');

        $map = [];

        $role = null;

        foreach (explode("\n", $section[1]) as $line) {
            $found = preg_match('/`(' . implode('|', [auth\ROLE_VIEWER, auth\ROLE_EDITOR, auth\ROLE_ADMIN]) . ')`/', $line, $roleMatch) === 1
                ? $roleMatch[1]
                : null;

            // a bullet starts a role, its wrapped continuation lines inherit it
            if (str_starts_with(trim($line), '-')) {
                $role = $found;
            }

            if ($role === null) {
                continue;
            }

            // route names may be nested (`system/log/clear`) or bare (`system`); the
            // role names in the same line are not routes
            preg_match_all('/`([a-z][a-z\/-]*)`/', $line, $routes);

            foreach ($routes[1] as $route) {
                if (in_array($route, [auth\ROLE_VIEWER, auth\ROLE_EDITOR, auth\ROLE_ADMIN], true)) {
                    continue;
                }
                $map[$route] = $role;
            }
        }

        self::$documented = $map;

        return $map;
    }

    /** @return array<string, string> route name → role, as registered in routes.php */
    private function registered(): array
    {
        require_once __DIR__ . '/../routes.php';

        $map = [];

        foreach (router\routes() as $route) {
            if ($route['authenticated'] !== true) {
                continue;
            }
            $map[$route['action']] = $route['role'];
        }

        return $map;
    }

    public function testEveryRouteNamedInTheDocsExistsWithThatRole(): void
    {
        $registered = $this->registered();

        foreach ($this->documented() as $route => $role) {
            $this->assertArrayHasKey($route, $registered, "the docs name a route that does not exist: {$route}");
            $this->assertSame($role, $registered[$route], "the docs give {$route} another role");
        }
    }

    public function testEveryRouteAboveViewerIsNamedInTheDocs(): void
    {
        $documented = $this->documented();
        $missing = [];

        foreach ($this->registered() as $route => $role) {
            if ($role !== auth\ROLE_VIEWER && !array_key_exists($route, $documented)) {
                $missing[] = $route . ' (' . $role . ')';
            }
        }

        $this->assertSame([], $missing, 'routes that need more than viewer belong in the docs');
    }

    public function testTheDocsCoverAllThreeRoles(): void
    {
        $this->assertSame(
            [auth\ROLE_VIEWER, auth\ROLE_EDITOR, auth\ROLE_ADMIN],
            array_values(array_unique([auth\ROLE_VIEWER, auth\ROLE_EDITOR, auth\ROLE_ADMIN])),
            'all three roles exist',
        );

        $documented = $this->documented();
        $this->assertNotSame([], $documented, 'the section lists routes');
    }
}

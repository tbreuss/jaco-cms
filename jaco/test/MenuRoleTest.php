<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\auth;
use jaco\core\router;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * The navigation and the route table have to agree.
 *
 * Both directions broke once: a menu entry stayed admin after the routes opened
 * for editors, and later it was greyed out again after applying became an
 * editor's right. Neither PHPUnit nor the browser suite noticed, because both
 * only look at one side.
 *
 * The check follows the chain menu entry -> view -> model -> api action -> role.
 *
 * A view usually reads more than it writes: the page template editor lists the
 * templates and reads one file with the editor's rights, and only the update is
 * the admin's. The menu entry has to allow the page at all, so the highest role
 * an entry's view reaches decides - reading one route alone would let an admin
 * page hang off an editor route.
 */
#[TestDox('Menu roles match the route table')]
final class MenuRoleTest extends TestCase
{
    public function testEveryMenuEntryUsesTheRoleItsRouteRequires(): void
    {
        $routes = $this->actionForPath();
        $roles = $this->routeRoles();

        $mismatch = [];

        foreach ($this->menuEntries() as $label => $entry) {
            [$path, $role] = $entry;

            $actions = $routes[$path] ?? null;
            if ($actions === null) {
                $mismatch[] = $label . ': no view loads ' . $path;
                continue;
            }

            $rolesOfView = $this->rolesOf($actions, $roles);
            if ($rolesOfView === null) {
                $mismatch[] = $label . ': ' . \implode(', ', $actions) . ' names a route that is not registered';
                continue;
            }

            if (!\in_array($role, $this->rolesAtLeast($rolesOfView), true)) {
                $mismatch[] = \sprintf('%s: menu says %s, %s only reach %s', $label, $role, \implode(', ', $actions), \implode('/', $rolesOfView));
            }
        }

        $this->assertSame([], $mismatch, "menu and route table disagree:\n  " . \implode("\n  ", $mismatch));
    }

    /**
     * roleLink('role', '/path', t('Label')) from the layout, keyed by label.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private function menuEntries(): array
    {
        $layout = (string) \file_get_contents($this->root() . '/jaco/frontend/layout/Default.js');

        \preg_match_all(
            "/roleLink\\('(\\w+)', '([^']+)', t\\('([^']+)'\\)\\)/",
            $layout,
            $matches,
            \PREG_SET_ORDER,
        );

        $entries = [];
        foreach ($matches as [, $role, $path, $label]) {
            $entries[$label] = [$path, $role];
        }

        return $entries;
    }

    /**
     * The api actions a view calls, reached through its model imports, keyed by
     * route path: every model a view imports contributes, plus the calls the
     * view makes itself.
     *
     * @return array<string, list<string>>
     */
    private function actionForPath(): array
    {
        $app = (string) \file_get_contents($this->root() . '/jaco/frontend/app.js');

        // the route table is hand formatted, so the pattern has to tolerate the
        // whitespace between the parts
        \preg_match_all(
            "~'([^']+)'\s*:\s*\{\s*onmatch\s*:\s*\(\s*\)\s*=>\s*\{\s*return\s+loadComponent\(\s*'([^']+)'~",
            $app,
            $matches,
            \PREG_SET_ORDER,
        );

        $map = [];
        foreach ($matches as [, $path, $view]) {
            $actions = $this->actionsOfView($view);
            if ($actions !== []) {
                $map[$path] = $actions;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function actionsOfView(string $view): array
    {
        $file = $this->root() . '/jaco/frontend/view/' . $view;
        if (!\is_file($file)) {
            return [];
        }

        $source = (string) \file_get_contents($file);
        $actions = [];

        // the import reaches the model directory from the view, one or two levels up
        \preg_match_all("~from '(?:\.\./)+model/([^']+)'~", $source, $matches);

        foreach ($matches[1] as $model) {
            $actions = [...$actions, ...$this->apiCalls($this->root() . '/jaco/frontend/model/' . $model)];
        }

        // a view without a model calls the api itself (the inline editor)
        return \array_values(\array_unique([...$actions, ...$this->apiCalls($file)]));
    }

    /**
     * The endpoints a file calls, skipping the translations request every view
     * makes on load.
     *
     * @return list<string>
     */
    private function apiCalls(string $file): array
    {
        if (!\is_file($file)) {
            return [];
        }

        $source = (string) \file_get_contents($file);

        \preg_match_all("/api\.(?:get|post)\('([^']+)'/", $source, $matches);

        return \array_values(\array_filter(
            \array_unique($matches[1]),
            static fn(string $action): bool => $action !== 'translations',
        ));
    }

    /**
     * The roles of the routes a view calls; null when one of them is not registered.
     *
     * @param list<string> $actions
     * @param array<string, string> $roles
     * @return list<string>|null
     */
    private function rolesOf(array $actions, array $roles): ?array
    {
        $found = [];
        foreach ($actions as $action) {
            $role = $roles[$action] ?? null;
            if ($role === null) {
                return null;
            }
            $found[] = $role;
        }

        return \array_values(\array_unique($found));
    }

    /**
     * The roles that reach at least one of the given roles.
     *
     * @param list<string> $required
     * @return list<string>
     */
    private function rolesAtLeast(array $required): array
    {
        $rank = [auth\ROLE_EDITOR => 1, auth\ROLE_ADMIN => 2];
        $minimum = 99;
        foreach ($required as $role) {
            $minimum = \min($minimum, $rank[$role] ?? 99);
        }

        return \array_keys(\array_filter($rank, static fn(int $value): bool => $value >= $minimum));
    }

    /** @return array<string, string> */
    private function routeRoles(): array
    {
        require_once $this->root() . '/jaco/routes.php';

        $roles = [];
        foreach (router\routes() as $route) {
            if ($route['authenticated'] === true) {
                $roles[$route['action']] = $route['role'];
            }
        }

        return $roles;
    }

    private function root(): string
    {
        return \dirname(__DIR__, 2);
    }
}

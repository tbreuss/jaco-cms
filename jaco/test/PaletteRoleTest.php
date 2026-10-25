<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\router;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * The command palette must not offer a command its route refuses.
 *
 * `List backups` had no role at all, so an editor saw it, ran it and landed on
 * the access-denied page. The palette carries its own role list, like the menu,
 * and nothing compared the two.
 *
 * The check follows the chain command -> route path -> view -> model -> api
 * action -> role, the same way MenuRoleTest does for the menu.
 */
#[TestDox('Command palette roles match the route table')]
final class PaletteRoleTest extends TestCase
{
    public function testEveryPaletteCommandUsesTheRoleItsRouteRequires(): void
    {
        $actions = $this->actionForPath();
        $roles = $this->routeRoles();
        $problems = [];

        foreach ($this->commands() as $id => $command) {
            $path = $this->pathOf($id);
            if ($path === null) {
                // theme and page actions own no page of their own: they open a
                // route a menu entry covers, or they change nothing but the theme
                continue;
            }

            $action = $actions[$path] ?? null;
            if ($action === null) {
                $problems[] = $id . ': no view loads ' . $path;
                continue;
            }

            $required = $roles[$action] ?? null;
            if ($required === null) {
                $problems[] = $id . ': ' . $action . ' is not a registered route';
                continue;
            }

            foreach ($command as $role) {
                if (!\in_array($role, $this->rolesAtLeast($required), true)) {
                    $problems[] = \sprintf('%s: palette allows %s, %s needs %s', $id, \implode('/', $command), $action, $required);
                }
            }
        }

        $this->assertSame([], $problems, "palette and route table disagree:\n  " . \implode("\n  ", $problems));
    }

    public function testEveryBackendCommandCarriesARole(): void
    {
        // a navigation command without a role is exactly the hole this guards
        $missing = [];

        foreach ($this->commands() as $id => $roles) {
            if ($this->pathOf($id) !== null && $roles === []) {
                $missing[] = $id;
            }
        }

        $this->assertSame([], $missing, 'commands that open a page need a role: ' . \implode(', ', $missing));
    }

    /**
     * The route a command opens, read from its `run` body.
     */
    private function pathOf(string $id): ?string
    {
        $source = (string) \file_get_contents($this->root() . '/jaco/frontend/lib/commands.js');

        \preg_match('/id: \'' . \preg_quote($id, '/') . '\'(.*?)\n  \}/s', $source, $block);
        if (!isset($block[1])) {
            return null;
        }

        \preg_match("/m\\.route\\.set\\('([^']+)'\\)/", $block[1], $path);

        return $path[1] ?? null;
    }

    /** @return array<string, list<string>> command id => roles */
    private function commands(): array
    {
        $source = (string) \file_get_contents($this->root() . '/jaco/frontend/lib/commands.js');

        \preg_match_all('/id: \'([^\']+)\'(.*?)\n  \}/s', $source, $matches, \PREG_SET_ORDER);

        $commands = [];
        foreach ($matches as [, $id, $body]) {
            \preg_match("/roles: \\[([^\\]]*)\\]/", $body, $roles);
            $list = isset($roles[1])
                ? \array_values(\array_filter(\array_map(
                    static fn(string $role): string => \trim($role, " '\t\n"),
                    \explode(',', $roles[1]),
                )))
                : [];
            $commands[$id] = $list;
        }

        return $commands;
    }

    /**
     * Roles that satisfy the required one: the rank means every higher role does.
     *
     * @return list<string>
     */
    private function rolesAtLeast(string $required): array
    {
        $rank = ['editor' => 1, 'admin' => 2];
        $minimum = $rank[$required] ?? 99;

        return \array_keys(\array_filter($rank, static fn(int $value): bool => $value >= $minimum));
    }

    /** @return array<string, string> */
    private function actionForPath(): array
    {
        $app = (string) \file_get_contents($this->root() . '/jaco/frontend/app.js');

        \preg_match_all(
            "~'([^']+)'\\s*:\\s*\\{\\s*onmatch\\s*:\\s*\\(\\s*\\)\\s*=>\\s*\\{\\s*return\\s+loadComponent\\(\\s*'([^']+)'~",
            $app,
            $matches,
            \PREG_SET_ORDER,
        );

        $map = [];
        foreach ($matches as [, $path, $view]) {
            $action = $this->firstApiCall($this->root() . '/jaco/frontend/view/' . $view);
            if ($action !== null) {
                $map[$path] = $action;
            }
        }

        return $map;
    }

    private function firstApiCall(string $file): ?string
    {
        if (!\is_file($file)) {
            return null;
        }

        $source = (string) \file_get_contents($file);

        \preg_match_all("/import \\{[^}]+\\} from '(?:\\.[^']*\\/)+model\\/([^']+)'/", $source, $models);

        foreach ($models[1] as $model) {
            $call = $this->apiAction($this->root() . '/jaco/frontend/model/' . $model);
            if ($call !== null) {
                return $call;
            }
        }

        return $this->apiAction($file);
    }

    private function apiAction(string $file): ?string
    {
        if (!\is_file($file)) {
            return null;
        }

        \preg_match_all("/api\\.(?:get|post)\\('([^']+)'/", (string) \file_get_contents($file), $matches);

        foreach ($matches[1] as $action) {
            if ($action !== 'translations') {
                return $action;
            }
        }

        return null;
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

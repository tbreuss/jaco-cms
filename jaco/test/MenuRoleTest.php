<?php declare(strict_types=1);

namespace jaco\tests;

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

            $action = $routes[$path] ?? null;
            if ($action === null) {
                $mismatch[] = $label . ': no view loads ' . $path;
                continue;
            }

            $required = $roles[$action] ?? null;
            if ($required === null) {
                $mismatch[] = $label . ': ' . $action . ' is not a registered route';
                continue;
            }

            if ($required !== $role) {
                $mismatch[] = \sprintf('%s: menu says %s, %s needs %s', $label, $role, $action, $required);
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
     * The first api action a view calls, reached through its model import, keyed
     * by route path.
     *
     * @return array<string, string>
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
            $action = $this->actionOfView($view);
            if ($action !== null) {
                $map[$path] = $action;
            }
        }

        return $map;
    }

    private function actionOfView(string $view): ?string
    {
        $file = $this->root() . '/jaco/frontend/view/' . $view;
        if (!\is_file($file)) {
            return null;
        }

        $source = (string) \file_get_contents($file);

        // the import reaches the model directory from the view, one or two levels up
        \preg_match_all("~from '(?:\\.\\./)+model/([^']+)'~", $source, $matches);

        foreach ($matches[1] as $model) {
            $call = $this->firstApiCall($this->root() . '/jaco/frontend/model/' . $model);
            if ($call !== null) {
                return $call;
            }
        }

        // a view without a model calls the api itself (the inline editor)
        return $this->firstApiCall($file);
    }

    /**
     * The endpoint a file talks to first, skipping the translations request every
     * view makes on load.
     */
    private function firstApiCall(string $file): ?string
    {
        if (!\is_file($file)) {
            return null;
        }

        $source = (string) \file_get_contents($file);

        if (\preg_match_all("/api\.(?:get|post)\('([^']+)'/", $source, $matches) === 0) {
            return null;
        }

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

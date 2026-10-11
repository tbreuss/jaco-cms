<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Map;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\router;

/**
 * Base class for tests that send requests through the real route table, so the
 * authentication check, the JSON schema validation and the domain code all run.
 */
abstract class ApiTestCase extends TestCase
{
    protected Config $config;

    /** @var array<string, list<string>> */
    private array $watchedDirs = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/../routes.php';

        $this->config = test_config();
    }

    protected function tearDown(): void
    {
        foreach (array_keys($this->watchedDirs) as $dir) {
            foreach ($this->newEntriesIn($dir) as $entry) {
                @unlink($dir . '/' . $entry);
            }
        }

        $this->watchedDirs = [];
    }

    /**
     * Remember the current content of a directory; tearDown removes everything a test adds.
     */
    protected function watchDir(string $dir): void
    {
        $this->watchedDirs[$dir] = $this->dirEntries($dir);
    }

    /**
     * @return list<string> files that were added since watchDir() was called
     */
    protected function newEntriesIn(string $dir): array
    {
        return array_values(array_diff($this->dirEntries($dir), $this->watchedDirs[$dir] ?? []));
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, string> $query additional query parameters besides "action"
     */
    protected function api(
        string $action,
        array $post = [],
        string $method = 'POST',
        bool $authenticated = true,
        array $query = [],
        ?Config $config = null,
    ): Response {
        $server = ['REQUEST_METHOD' => $method];

        if ($authenticated) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->token();
        }

        return router\dispatch(new Request(
            query: new Map(array_merge(['action' => $action], $query)),
            post: new Map($post),
            server: new Map($server),
            headers: new Map([]),
        ), $config ?? $this->config);
    }

    /**
     * Update a page through the API and return the file name of the backup this created.
     * The test must have called watchDir() with the backup directory before.
     */
    protected function createPageBackup(string $page, string $html): string
    {
        $this->api('page/update', ['fileName' => $page, 'html' => $html]);

        $newEntries = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $newEntries, 'exactly one backup is expected');

        return $newEntries[0];
    }

    protected function token(): string
    {
        return (string) auth\authenticate($this->config, $this->config->username, $this->config->password);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    protected function itemWithFile(array $items, string $file): array
    {
        foreach ($items as $item) {
            if (($item['file'] ?? null) === $file) {
                return $item;
            }
        }

        $this->fail('No item with file "' . $file . '" in the response.');
    }

    /** @return list<string> */
    private function dirEntries(string $dir): array
    {
        $entries = scandir($dir);

        if ($entries === false) {
            return [];
        }

        return array_values(array_diff($entries, ['.', '..']));
    }
}

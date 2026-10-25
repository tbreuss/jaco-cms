<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Response;
use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page list API (page/list)')]
final class ApiPageListTest extends ApiTestCase
{
    private const PAGE_A = 'api-list-a.html';
    private const PAGE_B = 'api-list-b.html';
    private const HIDDEN = 'api-list-hidden.html';

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        file_put_contents($this->config->webDir . '/' . self::PAGE_A, $this->page('List A'));
        file_put_contents($this->config->webDir . '/' . self::PAGE_B, $this->page('List B'));
    }

    public function testPagesAreListedWithTheirTitleAndWithoutContent(): void
    {
        $response = $this->api('page/list', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = $this->body($response);
        $this->assertSame('', $body['filter']);
        $this->assertSame(['created', 'desc'], $body['sort']);

        $item = $this->itemWithFile($body['items'], self::PAGE_A);
        $this->assertSame('List A', $item['title']);
        $this->assertNull($item['content']);
        $this->assertIsInt($item['created']);
    }

    public function testOperatorDataIsNotListedAsAPage(): void
    {
        // regression: the page walk excluded only the application, so a template or
        // a backup below jaco-data appeared as a page
        @mkdir($this->config->backupDir, 0755, true);
        $backup = $this->config->backupDir . '/20260101_000000_aaaa_de|index.html';
        file_put_contents($backup, $this->page('From a backup'));

        try {
            $body = $this->body($this->api('page/list', method: 'GET'));
            $files = array_column($body['items'], 'file');

            $this->assertNotContains('jaco-data/backups/20260101_000000_aaaa_de|index.html', $files);
            $this->assertSame([], array_filter($files, static fn(string $file): bool => str_starts_with($file, 'jaco-data/')));
        } finally {
            @unlink($backup);
        }
    }

    public function testTheOverviewDoesNotCountOperatorData(): void
    {
        @mkdir($this->config->backupDir, 0755, true);
        $backup = $this->config->backupDir . '/20260101_000000_bbbb_de|index.html';
        file_put_contents($backup, $this->page('From a backup'));

        try {
            $listed = count($this->body($this->api('page/list', method: 'GET'))['items']);
            $counted = $this->body($this->api('system', method: 'GET'))['overview']['pages'];

            // the overview counts what the page list shows: a backup adds no page
            $this->assertSame($listed, $counted, 'the overview counts the same pages as the list');
        } finally {
            @unlink($backup);
        }
    }

    public function testFilterNarrowsTheList(): void
    {
        $body = $this->body($this->api('page/list', method: 'GET', query: ['filter' => 'api-list-a']));

        $this->assertSame('api-list-a', $body['filter']);
        $this->assertCount(1, $body['items']);
        $this->assertSame(self::PAGE_A, $body['items'][0]['file']);
    }

    public function testSortIsReported(): void
    {
        $body = $this->body($this->api('page/list', method: 'GET', query: ['sort' => 'title']));

        $this->assertSame(['title', 'asc'], $body['sort']);
    }

    public function testInvalidSortIsRejected(): void
    {
        $response = $this->api('page/list', method: 'GET', query: ['sort' => 'unknown']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testPagesInTheAppDirAreNeverListed(): void
    {
        file_put_contents($this->config->appDir . '/' . self::HIDDEN, $this->page('Hidden'));

        try {
            $body = $this->body($this->api('page/list', method: 'GET', query: ['filter' => 'api-list-hidden']));

            $this->assertSame([], $body['items'], 'the app directory must stay invisible');
        } finally {
            @unlink($this->config->appDir . '/' . self::HIDDEN);
        }
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/list', method: 'GET', authenticated: false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    private function page(string $title): string
    {
        return "<!DOCTYPE html>\n<html lang=\"en\"><head><title>" . $title . "</title></head><body></body></html>\n";
    }

    /** @return array<string, mixed> */
    private function body(Response $response): array
    {
        return (array) json_decode((string) $response->body, true);
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Backup list API (backup/list)')]
final class ApiBackupListTest extends ApiTestCase
{
    private const PAGE = 'api-backup-list.html';

    private const ORIGINAL = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Backup list test</title></head>"
        . "<body><p>original</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::PAGE, self::ORIGINAL);
    }

    public function testBackupAppearsInTheListWithItsPageInformation(): void
    {
        $backup = $this->createPageBackup(self::PAGE, $this->changedPage());

        $response = $this->api('backup/list', method: 'GET');

        $this->assertSame(Status::OK, $response->status);
        $this->assertMatchesRegularExpression(
            '/^\d{8}_\d{6}_[a-z0-9]{4}_' . preg_quote(self::PAGE, '/') . '$/',
            $backup,
        );

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('', $body['filter']);
        $this->assertSame(['created', 'desc'], $body['sort']);

        $item = $this->itemWithFile($body['items'], $backup);
        $this->assertSame(self::PAGE, $item['name']);
        $this->assertSame('page', $item['type']);
        $this->assertSame('Backup list test', $item['title']);
        // the list does not ship the content, backup/view does
        $this->assertSame('', $item['content']);
        $this->assertIsInt($item['created']);
        $this->assertIsInt($item['backedUp']);
    }

    public function testFilterMatchesTheOriginalFileName(): void
    {
        $this->createPageBackup(self::PAGE, $this->changedPage());

        $response = $this->api('backup/list', method: 'GET', query: ['filter' => self::PAGE]);
        $body = (array) json_decode((string) $response->body, true);

        $this->assertNotEmpty($body['items']);
        $this->assertSame(self::PAGE, $body['filter']);

        foreach ($body['items'] as $item) {
            $this->assertSame(self::PAGE, $item['name']);
        }
    }

    public function testInvalidSortIsRejected(): void
    {
        // "file" is part of the response but is not a sort parameter of backups
        $response = $this->api('backup/list', method: 'GET', query: ['sort' => 'file']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('backup/list', method: 'GET', authenticated: false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }

    public function testBackupOlderThanRetentionIsPruned(): void
    {
        $backup = $this->createPageBackup(self::PAGE, $this->changedPage());
        touch($this->config->backupDir . '/' . $backup, time() - 91 * 86400);

        $response = $this->api('backup/list', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);

        foreach ($body['items'] as $item) {
            $this->assertNotSame($backup, $item['file']);
        }
        $this->assertFileDoesNotExist($this->config->backupDir . '/' . $backup);
    }

    public function testFreshBackupSurvivesPruning(): void
    {
        $backup = $this->createPageBackup(self::PAGE, $this->changedPage());

        $response = $this->api('backup/list', method: 'GET');
        $body = (array) json_decode((string) $response->body, true);

        $this->itemWithFile($body['items'], $backup);
        $this->assertFileExists($this->config->backupDir . '/' . $backup);
    }

    private function changedPage(): string
    {
        return "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Backup list test</title></head>"
            . "<body><p>changed</p></body></html>\n";
    }
}

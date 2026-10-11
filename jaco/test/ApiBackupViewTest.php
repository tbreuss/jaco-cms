<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Backup view API (backup/view)')]
final class ApiBackupViewTest extends ApiTestCase
{
    private const PAGE = 'api-backup-view.html';

    private const ORIGINAL = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Backup view test</title></head>"
        . "<body><p>original</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::PAGE, self::ORIGINAL);
    }

    public function testBackupIsReturnedWithItsContent(): void
    {
        $backup = $this->createPageBackup(self::PAGE, $this->changedPage());

        $response = $this->api('backup/view', method: 'GET', query: ['file' => $backup]);

        $this->assertSame(Status::OK, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame($backup, $body['file']);
        $this->assertSame(self::PAGE, $body['name']);
        $this->assertSame('page', $body['type']);
        $this->assertSame('Backup view test', $body['title']);
        $this->assertSame(self::ORIGINAL, $body['content']);
        $this->assertIsInt($body['created']);
        $this->assertIsInt($body['backedUp']);
    }

    public function testUnknownBackupIsRejected(): void
    {
        $response = $this->api('backup/view', method: 'GET', query: [
            'file' => '20200101_000000_aaaa_' . self::PAGE,
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testPageNameIsNotAcceptedAsBackup(): void
    {
        $response = $this->api('backup/view', method: 'GET', query: ['file' => self::PAGE]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('backup/view', method: 'GET', authenticated: false, query: ['file' => self::PAGE]);

        $this->assertSame(Status::FORBIDDEN, $response->status);
    }

    private function changedPage(): string
    {
        return "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Backup view test</title></head>"
            . "<body><p>changed</p></body></html>\n";
    }
}

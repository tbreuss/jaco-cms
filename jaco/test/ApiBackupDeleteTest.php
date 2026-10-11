<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Backup delete API (backup/delete)')]
final class ApiBackupDeleteTest extends ApiTestCase
{
    private const PAGE = 'api-backup-delete.html';

    private const ORIGINAL = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Backup delete test</title></head>"
        . "<body><p>original</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::PAGE, self::ORIGINAL);
    }

    public function testBackupIsDeleted(): void
    {
        $backup = $this->createPageBackup(self::PAGE, $this->changedPage());
        $this->assertFileExists($this->config->backupDir . '/' . $backup);

        $response = $this->api('backup/delete', ['file' => $backup]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertFileDoesNotExist($this->config->backupDir . '/' . $backup);
    }

    public function testUnknownBackupIsRejected(): void
    {
        $response = $this->api('backup/delete', ['file' => '20200101_000000_aaaa_' . self::PAGE]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
    }

    public function testPageNameIsNotAcceptedAsBackup(): void
    {
        $response = $this->api('backup/delete', ['file' => self::PAGE]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileExists($this->config->webDir . '/' . self::PAGE);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $backup = $this->createPageBackup(self::PAGE, $this->changedPage());

        $response = $this->api('backup/delete', ['file' => $backup], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileExists($this->config->backupDir . '/' . $backup);
    }

    private function changedPage(): string
    {
        return "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Backup delete test</title></head>"
            . "<body><p>changed</p></body></html>\n";
    }
}

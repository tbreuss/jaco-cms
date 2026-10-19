<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Backup restore API (backup/restore)')]
final class ApiBackupRestoreTest extends ApiTestCase
{
    private const PAGE = 'api-restore-test.html';

    private const ORIGINAL = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Restore test</title></head>"
        . "<body><p>original</p></body></html>\n";

    private const CHANGED = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Restore test</title></head>"
        . "<body><p>changed</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);

        file_put_contents($this->config->webDir . '/' . self::PAGE, self::ORIGINAL);
    }

    public function testBackupIsRestoredIntoTheOriginalPage(): void
    {
        // a page update backs up the content that is about to be replaced
        $update = $this->api('page/update', ['fileName' => self::PAGE, 'html' => self::CHANGED]);
        $this->assertSame(Status::CREATED, $update->status);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->backupDir . '/' . $backups[0]));

        // make the current page unrecognisable
        file_put_contents($this->config->webDir . '/' . self::PAGE, "<p>overwritten</p>\n");

        $response = $this->api('backup/restore', ['file' => $backups[0]]);

        $this->assertSame(Status::OK, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::PAGE));

        // the backup is moved into place, not copied
        $this->assertFileDoesNotExist($this->config->backupDir . '/' . $backups[0]);
    }

    public function testUnknownBackupIsRejected(): void
    {
        $response = $this->api('backup/restore', ['file' => '20200101_000000_aaaa_' . self::PAGE]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::PAGE));
    }

    public function testPageNameIsNotAcceptedAsBackup(): void
    {
        $response = $this->api('backup/restore', ['file' => self::PAGE]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::PAGE));
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('backup/restore', ['file' => self::PAGE], 'POST', false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::PAGE));
    }
}

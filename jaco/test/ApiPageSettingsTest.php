<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page settings API (page/settings)')]
final class ApiPageSettingsTest extends ApiTestCase
{
    private const PAGE = 'api-settings-test.html';

    private const ORIGINAL = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Settings test</title></head>"
        . "<body><p>keep me</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        file_put_contents($this->config->webDir . '/' . self::PAGE, self::ORIGINAL);
    }

    public function testMetadataIsUpdatedAndTheBodyIsKept(): void
    {
        $response = $this->api('page/settings', [
            'fileName' => self::PAGE,
            'title' => 'New title',
            'description' => 'A useful description.',
            'robots' => 'noindex,nofollow',
        ]);

        $this->assertSame(Status::OK, $response->status);

        $content = (string) file_get_contents($this->config->webDir . '/' . self::PAGE);
        $this->assertStringContainsString('<title>New title</title>', $content);
        $this->assertStringContainsString('<meta name="description" content="A useful description.">', $content);
        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $content);
        $this->assertStringContainsString('<p>keep me</p>', $content);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->backupDir . '/' . $backups[0]));
    }

    public function testInvalidRobotsValueIsRejected(): void
    {
        $response = $this->api('page/settings', [
            'fileName' => self::PAGE,
            'title' => 'New title',
            'description' => '',
            'robots' => 'maybe',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('robots', (string) $response->body);
        $this->assertSame(self::ORIGINAL, file_get_contents($this->config->webDir . '/' . self::PAGE));
    }

    public function testBlankTitleIsRejected(): void
    {
        $response = $this->api('page/settings', [
            'fileName' => self::PAGE,
            'title' => ' ',
            'description' => '',
            'robots' => '',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('pageTitle', (string) $response->body);
    }
}

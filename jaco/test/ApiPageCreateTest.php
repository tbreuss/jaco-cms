<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page create API (page/create)')]
final class ApiPageCreateTest extends ApiTestCase
{
    private const TEMPLATE = 'api-create-template.html';
    private const PAGE = 'api-created-page.html';

    private const TEMPLATE_CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Template title</title>"
        . "</head><body><p>template body</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->config->templatePageDir . '/' . self::TEMPLATE, self::TEMPLATE_CONTENT);
        $this->watchDir($this->config->webDir);
    }

    protected function tearDown(): void
    {
        @unlink($this->config->templatePageDir . '/' . self::TEMPLATE);
        parent::tearDown();
    }

    public function testPageIsCreatedFromTheTemplateWithTheGivenTitle(): void
    {
        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::CREATED, $response->status);

        $content = (string) file_get_contents($this->config->webDir . '/' . self::PAGE);
        $this->assertStringContainsString('<title>Created title</title>', $content);
        $this->assertStringContainsString('<p>template body</p>', $content);

        // the template itself stays untouched
        $this->assertSame(self::TEMPLATE_CONTENT, file_get_contents($this->config->templatePageDir . '/' . self::TEMPLATE));
    }

    public function testExistingPageIsNotOverwritten(): void
    {
        file_put_contents($this->config->webDir . '/' . self::PAGE, 'existing content');

        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('existing content', file_get_contents($this->config->webDir . '/' . self::PAGE));
    }

    public function testPageInTheAppDirIsRejected(): void
    {
        $response = $this->api('page/create', [
            'fileName' => $this->config->appDirName . '/' . self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist($this->config->appDir . '/' . self::PAGE);
    }

    public function testUnknownTemplateIsReportedAsServerError(): void
    {
        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => 'does-not-exist.html',
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertFileDoesNotExist($this->config->webDir . '/' . self::PAGE);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileDoesNotExist($this->config->webDir . '/' . self::PAGE);
    }
}

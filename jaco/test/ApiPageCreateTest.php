<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;

use const jaco\APP_DIR;
use const jaco\APP_DIR_NAME;
use const jaco\TEMPLATE_PAGE_DIR;
use const jaco\WEB_DIR;

/**
 * @testdox Page create API (page/create)
 */
final class ApiPageCreateTest extends ApiTestCase
{
    private const TEMPLATE = 'api-create-template.html';
    private const PAGE = 'api-created-page.html';

    private const TEMPLATE_CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Template title</title>"
        . "</head><body><p>template body</p></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(TEMPLATE_PAGE_DIR . '/' . self::TEMPLATE, self::TEMPLATE_CONTENT);
        $this->watchDir(WEB_DIR);
    }

    protected function tearDown(): void
    {
        @unlink(TEMPLATE_PAGE_DIR . '/' . self::TEMPLATE);
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

        $content = (string) file_get_contents(WEB_DIR . '/' . self::PAGE);
        $this->assertStringContainsString('<title>Created title</title>', $content);
        $this->assertStringContainsString('<p>template body</p>', $content);

        // the template itself stays untouched
        $this->assertSame(self::TEMPLATE_CONTENT, file_get_contents(TEMPLATE_PAGE_DIR . '/' . self::TEMPLATE));
    }

    public function testExistingPageIsNotOverwritten(): void
    {
        file_put_contents(WEB_DIR . '/' . self::PAGE, 'existing content');

        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertSame('existing content', file_get_contents(WEB_DIR . '/' . self::PAGE));
    }

    public function testPageInTheAppDirIsRejected(): void
    {
        $response = $this->api('page/create', [
            'fileName' => APP_DIR_NAME . '/' . self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertFileDoesNotExist(APP_DIR . '/' . self::PAGE);
    }

    public function testUnknownTemplateIsReportedAsServerError(): void
    {
        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => 'does-not-exist.html',
            'pageTitle' => 'Created title',
        ]);

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/' . self::PAGE);
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('page/create', [
            'fileName' => self::PAGE,
            'pageTemplate' => self::TEMPLATE,
            'pageTitle' => 'Created title',
        ], 'POST', false);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertFileDoesNotExist(WEB_DIR . '/' . self::PAGE);
    }
}

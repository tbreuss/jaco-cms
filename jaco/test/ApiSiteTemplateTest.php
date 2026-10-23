<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Site template API (template/site)')]
final class ApiSiteTemplateTest extends ApiTestCase
{
    private const PAGE = 'site-template-test.html';
    private const OTHER = 'site-template-other.html';

    protected function setUp(): void
    {
        parent::setUp();
        $this->watchDir($this->config->webDir);
        $this->watchDir($this->config->backupDir);
        $this->watchDir($this->config->templateSiteDir);

        @mkdir($this->config->templateSiteDir, 0755, true);
        file_put_contents($this->config->webDir . '/' . self::PAGE, '<html><body><footer data-jaco-template="footer">old</footer></body></html>');
        file_put_contents($this->config->webDir . '/' . self::OTHER, '<html><body><p>other</p></body></html>');
    }

    public function testTemplateCanBeListedAndViewed(): void
    {
        file_put_contents($this->config->templateSiteDir . '/footer.html', '<footer>old</footer>');

        $list = $this->api('template/site/list', method: 'GET');
        $this->assertSame(Status::OK, $list->status);
        $items = json_decode((string) $list->body, true);
        $this->assertSame('footer.html', $items[0]['file']);
        $this->assertSame('footer', $items[0]['name']);

        $view = $this->api('template/site/view', method: 'GET', query: ['file' => 'footer.html']);
        $this->assertSame(Status::OK, $view->status);
        $body = json_decode((string) $view->body, true);
        $this->assertSame('<footer>old</footer>', $body['content']);
    }

    public function testUpdateWithApplyReplacesMarkedElementsWithoutBackups(): void
    {
        $response = $this->api('template/site/update', [
            'fileName' => 'footer.html',
            'content' => '<footer><a href="de/impressum.html">Impressum</a></footer>',
            'applyToAll' => true,
        ]);

        $this->assertSame(Status::OK, $response->status);
        $body = json_decode((string) $response->body, true);
        $this->assertSame(1, $body['appliedFiles']);
        $this->assertSame(1, $body['appliedElements']);

        $content = (string) file_get_contents($this->config->webDir . '/' . self::PAGE);
        $this->assertStringContainsString('<footer data-jaco-template="footer"><a href="de/impressum.html">Impressum</a></footer>', $content);
        $this->assertStringNotContainsString('>old<', $content);

        $this->assertSame([], $this->newEntriesIn($this->config->backupDir), 'apply must not create backups');
    }


    public function testLanguageSpecificTemplatesStaySeparate(): void
    {
        $enPage = 'site-template-lang-en.html';
        $dePage = 'site-template-lang-de.html';
        file_put_contents($this->config->webDir . '/' . $enPage, '<html><body><footer data-jaco-template="footer-en">old en</footer></body></html>');
        file_put_contents($this->config->webDir . '/' . $dePage, '<html><body><footer data-jaco-template="footer-de">old de</footer></body></html>');

        $en = $this->api('template/site/update', [
            'fileName' => 'footer-en.html',
            'content' => '<footer>English footer</footer>',
            'applyToAll' => true,
        ]);
        $de = $this->api('template/site/update', [
            'fileName' => 'footer-de.html',
            'content' => '<footer>Deutscher Footer</footer>',
            'applyToAll' => true,
        ]);

        $this->assertSame(Status::OK, $en->status);
        $this->assertSame(Status::OK, $de->status);
        $this->assertStringContainsString('<footer data-jaco-template="footer-en">English footer</footer>', (string) file_get_contents($this->config->webDir . '/' . $enPage));
        $this->assertStringContainsString('<footer data-jaco-template="footer-de">Deutscher Footer</footer>', (string) file_get_contents($this->config->webDir . '/' . $dePage));
        $this->assertStringNotContainsString('Deutscher Footer', (string) file_get_contents($this->config->webDir . '/' . $enPage));
        $this->assertStringNotContainsString('English footer', (string) file_get_contents($this->config->webDir . '/' . $dePage));
    }
    public function testPreviewCountsPagesAndElementsWithoutWriting(): void
    {
        $response = $this->api('template/site/preview', ['fileName' => 'footer.html']);

        $this->assertSame(Status::OK, $response->status);
        $body = json_decode((string) $response->body, true);
        $this->assertSame(1, $body['elements']);
        $this->assertSame([['file' => self::PAGE, 'elements' => 1]], $body['files']);
        $this->assertSame([], $this->newEntriesIn($this->config->backupDir));
    }

    public function testInvalidTemplateIsRejectedAndNothingIsWritten(): void
    {
        $templateDir = $this->config->templateSiteDir;
        $original = (string) file_get_contents($this->config->webDir . '/' . self::PAGE);

        $response = $this->api('template/site/update', [
            'fileName' => 'footer.html',
            'content' => '<footer>one</footer><footer>two</footer>',
            'applyToAll' => true,
        ]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
        $this->assertStringContainsString('content', (string) $response->body);
        $this->assertSame($original, file_get_contents($this->config->webDir . '/' . self::PAGE));
        $this->assertFalse(is_file($templateDir . '/footer.html'));
    }
}

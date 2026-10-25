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

    public function testUpdateWithApplyReplacesMarkedElementsAndBacksEachPageUp(): void
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

        // applying rewrites the structure of every page: each one is backed up first
        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups, 'the rewritten page is backed up');
        $this->assertStringContainsString('site-template-test.html', (string) $backups[0]);
    }

    public function testTheBackupHoldsTheContentBeforeTheApply(): void
    {
        $this->api('template/site/update', [
            'fileName' => 'footer.html',
            'content' => '<footer>new</footer>',
            'applyToAll' => true,
        ]);

        $backups = $this->newEntriesIn($this->config->backupDir);
        $this->assertCount(1, $backups);
        $backup = (string) file_get_contents($this->config->backupDir . '/' . $backups[0]);

        $this->assertStringContainsString('<footer data-jaco-template="footer">old</footer>', $backup);
        $this->assertStringNotContainsString('>new<', $backup);
    }

    public function testPagesWithoutTheMarkerAreNotBackedUp(): void
    {
        $response = $this->api('template/site/update', [
            'fileName' => 'footer.html',
            'content' => '<footer>new</footer>',
            'applyToAll' => true,
        ]);

        $this->assertSame(Status::OK, $response->status);
        $body = json_decode((string) $response->body, true);
        $this->assertSame(1, $body['appliedFiles'], 'only the marked page is touched');

        foreach ($this->newEntriesIn($this->config->backupDir) as $backup) {
            $this->assertStringNotContainsString('site-template-other.html', (string) $backup);
        }
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
    public function testOperatorDataIsNotTreatedAsContent(): void
    {
        // Regression: only the app directory used to be excluded, so the operator
        // data below jaco-data was walked as well. A template was applied to its own
        // source and to every backup that carried a copy of the marker.
        @mkdir($this->config->backupDir, 0755, true);
        $ownTemplate = $this->config->templateSiteDir . '/footer.html';
        $backup = $this->config->backupDir . '/20260101_000000_aaaa_de|index.html';
        file_put_contents($ownTemplate, '<footer data-jaco-template="footer">old</footer>');
        file_put_contents($backup, '<html><body><footer data-jaco-template="footer">backup</footer></body></html>');

        $found = array_map(
            static fn($file) => $file->file,
            \jaco\domain\page\find_public_pages($this->config),
        );

        $this->assertContains(self::PAGE, $found, 'a real page is found');
        $this->assertNotContains('jaco-data/templates/site/footer.html', $found, 'the template source is no page');
        $this->assertNotContains('jaco-data/backups/20260101_000000_aaaa_de|index.html', $found, 'a backup is no page');
        $this->assertSame([], array_filter(
            $found,
            static fn(string $file) => \str_starts_with($file, 'jaco-data/'),
        ), 'nothing below the operator data is a page');
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

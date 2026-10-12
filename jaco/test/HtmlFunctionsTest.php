<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\filesystem;
use jaco\core\html;

#[TestDox('HTML Sanitizing')]
final class HtmlFunctionsTest extends TestCase
{
    /**
     * @var array<string, array{string, string}> [input, expected] pairs of dangerous html
     */
    private const DANGEROUS_HTML = [
        'script tag' => ['<p>ok</p><script>alert(1)</script>', '<p>ok</p>'],
        'onclick handler' => ['<p onclick="alert(1)">ok</p>', '<p>ok</p>'],
        'javascript href' => ['<a href="javascript:alert(1)">x</a>', '<a>x</a>'],
        'iframe' => ['<p>a</p><iframe src="https://evil.example"></iframe><p>b</p>', '<p>a</p><p>b</p>'],
        'svg onload' => ['<svg onload=alert(1)>x</svg><p>ok</p>', '<p>ok</p>'],
        'img onerror' => ['<img src="x" onerror="alert(1)">', '<img src="x">'],
        'style tag' => ['<style>body{display:none}</style><p>ok</p>', '<p>ok</p>'],
        'data href' => ['<a href="data:text/html,<script>alert(1)</script>">x</a>', '<a>x</a>'],
        'tab in scheme' => ["<a href=\"jav\tascript:alert(1)\">x</a>", '<a>x</a>'],
        'entity encoded' => ['<a href="&#106;avascript:alert(1)">x</a>', '<a>x</a>'],
        'nested unknown unwraps' => ['<p>keep <foo>bold</foo></p>', '<p>keep bold</p>'],
        'form unwrapped' => ['<form action="javascript:alert(1)"><p>inside</p></form>', '<p>inside</p>'],
        'meta removed' => ['<meta http-equiv="refresh" content="0;url=javascript:alert(1)"><p>ok</p>', '<p>ok</p>'],
    ];

    /**
     * @var array<string, array{string, string}> [input, expected] pairs of legitimate html
     */
    private const LEGITIMATE_HTML = [
        'bold link' => ['<p><a href="https://example.com" target="_blank">x</a> <strong>b</strong></p>', '<p><a href="https://example.com" target="_blank">x</a> <strong>b</strong></p>'],
        'mailto' => ['<a href="mailto:a@b.c">mail</a>', '<a href="mailto:a@b.c">mail</a>'],
        'relative link' => ['<a href="/page">x</a>', '<a href="/page">x</a>'],
        'data-jaco attrs' => ['<img src="upload/a.png" data-jaco-editable-image data-jaco-editable-image-extended>', '<img src="upload/a.png" data-jaco-editable-image data-jaco-editable-image-extended>'],
        'utf-8' => ['<p>Grüße — über</p>', '<p>Grüße — über</p>'],
        'heading list table' => ['<h1>T</h1><ul><li>a</li></ul><table><tr><td colspan="2">x</td></tr></table>', '<h1>T</h1><ul><li>a</li></ul><table><tr><td colspan="2">x</td></tr></table>'],
        'data-url image survives' => ['<img src="data:image/png;base64,iVBOR">', '<img src="data:image/png;base64,iVBOR">'],
    ];

    protected function setUp(): void
    {
        $config = test_config();
        if (filesystem\dir_exists($config->webDir, 'test')) {
            filesystem\remove_dir_rec($config->webDir, 'test');
        }
        filesystem\make_dir_rec($config, $config->webDir, 'test');
    }

    protected function tearDown(): void
    {
        $config = test_config();
        if (filesystem\dir_exists($config->webDir, 'test')) {
            filesystem\remove_dir_rec($config->webDir, 'test');
        }
        if (filesystem\dir_exists($config->backupDir, 'test')) {
            filesystem\remove_dir_rec($config->backupDir, 'test');
        }
        // page backups are stored flat with "|" instead of "/", so the
        // backup of test/xss.html has to be removed by file name
        foreach ((array) glob($config->backupDir . '/*test|xss.html') as $backup) {
            @unlink($backup);
        }
    }

    public function testDangerousHtmlIsRemoved(): void
    {
        foreach (self::DANGEROUS_HTML as $label => [$input, $expected]) {
            $this->assertSame($expected, html\sanitize_html($input), $label);
        }
    }

    public function testLegitimateHtmlIsKept(): void
    {
        foreach (self::LEGITIMATE_HTML as $label => [$input, $expected]) {
            $this->assertSame($expected, html\sanitize_html($input), $label);
        }
    }

    public function testBodyIsReplacedAndTheHeadIsKept(): void
    {
        $document = '<!DOCTYPE html>' . "\n"
            . '<html><head><title>T</title><base href="/"></head>'
            . '<body class="main"><p>old</p></body></html>';

        $result = html\replace_body($document, '<p>new</p>');

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<head><title>T</title><base href="/"></head>', $result);
        $this->assertStringContainsString('<body class="main"><p>new</p></body>', $result);
        $this->assertStringNotContainsString('<p>old</p>', $result);
    }

    public function testFragmentIsUsedAsBodyWhenTheDocumentHasNoBody(): void
    {
        $this->assertSame('<p>new</p>', html\replace_body('<p>old</p>', '<p>new</p>'));
        $this->assertSame('<p>new</p>', html\replace_body('', '<p>new</p>'));
    }

    public function testBodyIsNotRunThroughBackreferenceReplacement(): void
    {
        $this->assertSame(
            '<body><p>price: $1</p></body>',
            html\replace_body('<body><p>old</p></body>', '<p>price: $1</p>'),
        );
    }

    public function testEditableMarkersSurviveSanitizing(): void
    {
        $html = '<div data-jaco-editable><h1>Block</h1></div>'
            . '<p data-jaco-editable-line>Zeile</p>'
            . '<a href="/x" data-jaco-editable-link>Link</a>'
            . '<img src="upload/a.png" data-jaco-editable-image>';

        $result = html\sanitize_html($html);

        // without these the page cannot be edited in place any more
        $this->assertStringContainsString('data-jaco-editable>', $result);
        $this->assertStringContainsString('data-jaco-editable-line>', $result);
        $this->assertStringContainsString('data-jaco-editable-link>', $result);
        $this->assertStringContainsString('data-jaco-editable-image>', $result);
    }

    public function testContentListMarkersSurviveSanitizing(): void
    {
        $html = '<div data-jaco-content-list data-jaco-fragment="news.html" data-jaco-title="Add news" data-jaco-pos="top">'
            . '<div data-jaco-content-item><h2 data-jaco-editable-line>Headline</h2></div>'
            . '</div>';

        $result = html\sanitize_html($html);

        $this->assertStringContainsString('data-jaco-content-list', $result);
        $this->assertStringContainsString('data-jaco-content-item', $result);
        $this->assertStringContainsString('data-jaco-fragment="news.html"', $result);
        $this->assertStringContainsString('data-jaco-title="Add news"', $result);
        $this->assertStringContainsString('data-jaco-pos="top"', $result);
        $this->assertStringContainsString('data-jaco-editable-line', $result);
    }

    public function testGalleryMarkerSurvivesSanitizing(): void
    {
        $html = '<a href="/keep.html"><img data-jaco-editable-image data-jaco-editable-image-gallery'
            . ' data-jaco-gallery-full="data:image/png;base64,AAAA" src="upload/a.png" alt="test"></a>';

        $result = html\sanitize_html($html);

        $this->assertStringContainsString('data-jaco-editable-image-gallery', $result);
        $this->assertStringNotContainsString('data-jaco-gallery-full', $result);
        $this->assertStringContainsString('<a href="/keep.html">', $result);
    }

    public function testYouTubeEmbedSurvivesSanitizing(): void
    {
        $html = '<p>Watch</p><iframe width="560" height="315"'
            . ' src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Video"'
            . ' frameborder="0" allowfullscreen onclick="steal()"></iframe>';

        $result = html\sanitize_html($html);

        $this->assertStringContainsString('src="https://www.youtube.com/embed/dQw4w9WgXcQ"', $result);
        $this->assertStringContainsString('allowfullscreen', $result);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function testNonYouTubeIframeIsRemoved(): void
    {
        $html = '<p>a</p><iframe src="https://evil.example/player"></iframe><p>b</p>'
            . '<p>c</p><iframe src="https://player.vimeo.com/video/123"></iframe><p>d</p>';

        $result = html\sanitize_html($html);

        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringContainsString('<p>a</p>', $result);
        $this->assertStringContainsString('<p>d</p>', $result);
    }

    public function testUpdatePageSanitizesContent(): void
    {
        $config = test_config();
        $file = 'test/xss.html';
        $this->assertTrue(filesystem\write_file($config, $config->webDir, $file, '<p>old</p>'));

        $malicious = '<h1>Titel</h1><p onclick="steal()">Text <strong>fett</strong></p><script>alert(document.cookie)</script><a href="javascript:alert(1)">böser Link</a><iframe src="https://evil.example"></iframe>';

        $payload = \jaco\domain\page\update_page($config, $file, $malicious);

        $this->assertTrue($payload->isSuccess);
        $this->assertSame('<h1>Titel</h1><p>Text <strong>fett</strong></p><a>böser Link</a>', filesystem\read_file($config->webDir, $file));
    }
}

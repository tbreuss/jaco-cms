<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\filesystem;
use jaco\core\html;

use const jaco\BACKUP_DIR;
use const jaco\WEB_DIR;

/**
 * @testdox HTML Sanitizing
 */
final class HtmlFunctionsTest extends TestCase
{
    /**
     * @var array<int, array{string, string}> [input, expected] pairs of dangerous html
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
     * @var array<int, array{string, string}> [input, expected] pairs of legitimate html
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
        if (filesystem\dir_exists(WEB_DIR, 'test')) {
            filesystem\remove_dir_rec(WEB_DIR, 'test');
        }
        filesystem\make_dir_rec(WEB_DIR, 'test');
    }

    protected function tearDown(): void
    {
        if (filesystem\dir_exists(WEB_DIR, 'test')) {
            filesystem\remove_dir_rec(WEB_DIR, 'test');
        }
        if (filesystem\dir_exists(BACKUP_DIR, 'test')) {
            filesystem\remove_dir_rec(BACKUP_DIR, 'test');
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

    public function testUpdatePageSanitizesContent(): void
    {
        $file = 'test/xss.html';
        $this->assertTrue(filesystem\write_file(WEB_DIR, $file, '<p>old</p>'));

        $malicious = '<h1>Titel</h1><p onclick="steal()">Text <strong>fett</strong></p><script>alert(document.cookie)</script><a href="javascript:alert(1)">böser Link</a><iframe src="https://evil.example"></iframe>';

        $payload = \jaco\domain\page\update_page($file, $malicious);

        $this->assertTrue($payload->isSuccess);
        $this->assertSame('<h1>Titel</h1><p>Text <strong>fett</strong></p><a>böser Link</a>', filesystem\read_file(WEB_DIR, $file));
    }
}

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
        'svg onload' => ['<svg onload=alert(1)>x</svg><p>ok</p>', '<svg>x</svg><p>ok</p>'],
        'img onerror' => ['<img src="x" onerror="alert(1)">', '<img src="x">'],
        'style tag' => ['<style>body{display:none}</style><p>ok</p>', '<p>ok</p>'],
        'data href' => ['<a href="data:text/html,<script>alert(1)</script>">x</a>', '<a>x</a>'],
        'tab in scheme' => ["<a href=\"jav\tascript:alert(1)\">x</a>", '<a>x</a>'],
        'entity encoded' => ['<a href="&#106;avascript:alert(1)">x</a>', '<a>x</a>'],
        'protocol-relative href' => ['<a href="//evil.example/x">x</a>', '<a>x</a>'],
        'backslash href' => ['<a href="\\\\evil.example\\x">x</a>', '<a>x</a>'],
        'protocol-relative img' => ['<img src="//evil.example/x.png">', '<img>'],
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
        'data-jaco attrs' => ['<img src="upload/a.png" data-jaco-image data-jaco-image--mode="fill" width="320">', '<img src="upload/a.png" data-jaco-image data-jaco-image--mode="fill" width="320">'],
        'utf-8' => ['<p>Grüße — über</p>', '<p>Grüße — über</p>'],
        'heading list table' => ['<h1>T</h1><ul><li>a</li></ul><table><tr><td colspan="2">x</td></tr></table>', '<h1>T</h1><ul><li>a</li></ul><table><tr><td colspan="2">x</td></tr></table>'],
        'data-url image survives' => ['<img src="data:image/png;base64,iVBOR">', '<img src="data:image/png;base64,iVBOR">'],
        'button survives with its attributes' => [
            '<button type="button" class="nav-toggle" aria-expanded="false" aria-label="Open">x</button>',
            '<button type="button" class="nav-toggle" aria-expanded="false" aria-label="Open">x</button>',
        ],
        'button loses script attributes' => [
            '<button formaction="https://evil.example" onclick="steal()">x</button>',
            '<button>x</button>',
        ],
        'svg icon survives' => [
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path></svg>',
            '<svg viewbox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path></svg>',
        ],
        'svg circle survives with its geometry' => [
            '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle></svg>',
            '<svg viewbox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle></svg>',
        ],
        'sanitizing a saved button changes nothing' => [
            '<li class="nav-toggle-item"><button type="button" class="nav-toggle" aria-expanded="false" aria-label="Open the navigation" title="Open the navigation"><svg viewbox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg></button></li>',
            '<li class="nav-toggle-item"><button type="button" class="nav-toggle" aria-expanded="false" aria-label="Open the navigation" title="Open the navigation"><svg viewbox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg></button></li>',
        ],
        'svg script is removed, shapes stay' => [
            '<svg viewBox="0 0 24 24"><script>alert(1)</script><path d="M4 7h16"></path></svg>',
            '<svg viewbox="0 0 24 24"><path d="M4 7h16"></path></svg>',
        ],
        'svg script url is removed' => [
            '<svg viewBox="0 0 24 24"><a href="javascript:alert(1)"><path d="M4 7h16"></path></a></svg>',
            '<svg viewbox="0 0 24 24"><a><path d="M4 7h16"></path></a></svg>',
        ],
        'semantic sectioning survives' => [
            '<header><nav><a href="/x">x</a></nav></header><main><article><section><p>t</p></section></article><aside><p>a</p></aside></main><footer><p>f</p></footer>',
            '<header><nav><a href="/x">x</a></nav></header><main><article><section><p>t</p></section></article><aside><p>a</p></aside></main><footer><p>f</p></footer>',
        ],
        'leading header keeps its content' => [
            '<header><p>f</p></header><div>rest</div>',
            '<header><p>f</p></header><div>rest</div>',
        ],
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
        foreach (glob($config->backupDir . '/*test|xss.html') ?: [] as $backup) {
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

    public function testMetadataCanBeReadAndUpdatedWithoutTouchingTheBody(): void
    {
        $document = '<!DOCTYPE html><html><head><title>Old</title>'
            . '<meta name="description" content="Old description">'
            . '<meta name="robots" content="index,follow"></head>'
            . '<body><p>keep</p></body></html>';

        $this->assertSame('Old description', html\get_description($document));
        $this->assertSame('index,follow', html\get_robots($document));

        $updated = html\set_title($document, 'New title & more');
        $updated = html\set_description($updated, 'New <description>');
        $updated = html\set_robots($updated, 'noindex,nofollow');

        $this->assertStringContainsString('<title>New title &amp; more</title>', $updated);
        $this->assertStringContainsString('<meta name="description" content="New &lt;description&gt;">', $updated);
        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $updated);
        $this->assertStringContainsString('<p>keep</p>', $updated);
        $this->assertSame(1, substr_count($updated, 'name="description"'));
        $this->assertSame(1, substr_count($updated, 'name="robots"'));
    }

    public function testEmptyMetadataIsRemoved(): void
    {
        $document = '<html><head><meta name="description" content="old"><meta name="robots" content="noindex,nofollow"></head><body></body></html>';

        $updated = html\set_description($document, '');
        $updated = html\set_robots($updated, '');

        $this->assertStringNotContainsString('name="description"', $updated);
        $this->assertStringNotContainsString('name="robots"', $updated);
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
        $html = '<div data-jaco-block><h1>Block</h1></div>'
            . '<p data-jaco-line>Zeile</p>'
            . '<a href="/x" data-jaco-link>Link</a>'
            . '<img src="upload/a.png" data-jaco-image>';

        $result = html\sanitize_html($html);

        // without these the page cannot be edited in place any more
        $this->assertStringContainsString('data-jaco-block>', $result);
        $this->assertStringContainsString('data-jaco-line>', $result);
        $this->assertStringContainsString('data-jaco-link>', $result);
        $this->assertStringContainsString('data-jaco-image>', $result);
    }

    public function testRepeaterMarkersSurviveSanitizing(): void
    {
        $html = '<div data-jaco-repeater="news.html" data-jaco-repeater--label="Add news"'
            . ' data-jaco-repeater--position="top">'
            . '<div><h2 data-jaco-line>Headline</h2></div>'
            . '</div>';

        $result = html\sanitize_html($html);

        $this->assertStringContainsString('data-jaco-repeater="news.html"', $result);
        $this->assertStringContainsString('data-jaco-repeater--label="Add news"', $result);
        $this->assertStringContainsString('data-jaco-repeater--position="top"', $result);
        $this->assertStringContainsString('data-jaco-line', $result);
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

    public function testSvgScriptIsRemoved(): void
    {
        [$ok, $cleaned] = html\sanitize_svg(
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle cx="1" cy="2" r="3"/></svg>',
        );

        $this->assertTrue($ok);
        $this->assertStringNotContainsString('<script', $cleaned);
        $this->assertStringContainsString('<circle', $cleaned);
    }

    public function testSvgHandlersAndScriptUrlsAreRemoved(): void
    {
        [$ok, $cleaned] = html\sanitize_svg(
            '<svg xmlns="http://www.w3.org/2000/svg"><circle onload="steal()" cx="1"/>'
            . '<a href="javascript:steal()"><rect width="1" height="2"/></a>'
            . '<foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><p>hi</p></body></foreignObject></svg>',
        );

        $this->assertTrue($ok);
        $this->assertStringNotContainsString('onload', $cleaned);
        $this->assertStringNotContainsString('javascript:', $cleaned);
        $this->assertStringNotContainsString('foreignObject', $cleaned);
        $this->assertStringContainsString('<circle', $cleaned);
        $this->assertStringContainsString('<rect', $cleaned);
    }

    public function testCleanSvgPassesThrough(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="1" cy="2" r="3"/></svg>';

        [$ok, $cleaned] = html\sanitize_svg($svg);

        $this->assertTrue($ok);
        $this->assertStringContainsString('<circle cx="1" cy="2" r="3"/>', $cleaned);
    }

    public function testBrokenSvgIsRejected(): void
    {
        $this->assertSame([false, ['Invalid SVG file.']], html\sanitize_svg('not xml at all'));
        $this->assertSame([false, ['Invalid SVG file.']], html\sanitize_svg('<html><p>no svg root</p></html>'));
    }
}

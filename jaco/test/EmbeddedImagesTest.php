<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\html;

#[TestDox('Embedded data-url images')]
final class EmbeddedImagesTest extends TestCase
{
    private const FILE = 'api-embed-image.png';
    private const SECOND_FILE = 'api-embed-image-1.png';
    private const GALLERY_FILE = 'api-embed-gallery.png';
    private const GALLERY_FULL_FILE = 'api-embed-gallery-full.png';

    protected function setUp(): void
    {
        // the function picks up errors from the global libxml buffer
        \libxml_clear_errors();
    }

    protected function tearDown(): void
    {
        $config = test_config();
        @unlink($config->mediaDir . '/' . self::FILE);
        @unlink($config->mediaDir . '/' . self::SECOND_FILE);
        @unlink($config->mediaDir . '/' . self::GALLERY_FILE);
        @unlink($config->mediaDir . '/' . self::GALLERY_FULL_FILE);
    }

    public function testHtmlWithoutEmbeddedImagesIsReturnedUnchanged(): void
    {
        $config = test_config();
        $html = '<html><body><p>nothing to do</p></body></html>';

        $this->assertSame([true, $html], html\handle_embedded_data_url_images($config, $html));
    }

    public function testImageWithoutAMarkerAttributeIsIgnored(): void
    {
        $config = test_config();
        $html = '<html><body><img src="data:image/png;base64,AAAA"'
            . ' data-jaco-editable-image-file="' . self::FILE . '"></body></html>';

        $this->assertSame([true, $html], html\handle_embedded_data_url_images($config, $html));
    }

    public function testBrokenBase64IsReported(): void
    {
        $config = test_config();
        [$status, $errors] = html\handle_embedded_data_url_images($config, $this->image('not-base64!!'));

        $this->assertFalse($status);
        $this->assertSame(['Could not decode image data: ' . self::FILE], $errors);
    }

    public function testDisallowedExtensionIsReported(): void
    {
        $config = test_config();
        [$status, $errors] = html\handle_embedded_data_url_images($config, $this->image('AAAA', 'evil.php'));

        $this->assertFalse($status);
        $this->assertSame(['Invalid file name or extension: evil.php'], $errors);
    }

    public function testFileNameWithADirectoryIsReported(): void
    {
        $config = test_config();
        [$status, $errors] = html\handle_embedded_data_url_images($config, $this->image('AAAA', 'sub/x.png'));

        $this->assertFalse($status);
        $this->assertSame(['Invalid file name or extension: sub/x.png'], $errors);
    }

    public function testImageIsWrittenAndTheSourceIsRewritten(): void
    {
        $config = test_config();
        [$status, $result] = html\handle_embedded_data_url_images($config, $this->image(base64_encode('png-bytes')));

        $this->assertTrue($status);
        $this->assertSame('png-bytes', file_get_contents($config->mediaDir . '/' . self::FILE));

        $result = (string) $result;
        $this->assertStringContainsString('src="media/' . self::FILE . '"', $result);
        $this->assertStringNotContainsString('data-jaco-editable-image-file', $result);
        $this->assertStringNotContainsString('base64', $result);
    }

    public function testAnExistingUploadIsNeverOverwritten(): void
    {
        $config = test_config();
        file_put_contents($config->mediaDir . '/' . self::FILE, 'old bytes');

        [$status, $result] = html\handle_embedded_data_url_images($config, $this->image(base64_encode('new bytes')));

        $this->assertTrue($status);
        $this->assertSame('old bytes', file_get_contents($config->mediaDir . '/' . self::FILE));
        $this->assertSame('new bytes', file_get_contents($config->mediaDir . '/' . self::SECOND_FILE));
        $this->assertStringContainsString('src="media/' . self::SECOND_FILE . '"', (string) $result);
    }

    public function testErrorsFromAnEarlierParseDoNotLeakIntoTheResult(): void
    {
        $config = test_config();
        // leave an unrelated error in the global libxml buffer
        $stale = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $stale->loadXML('<root><unclosed></root>');
        $this->assertNotEmpty(\libxml_get_errors(), 'the buffer has to contain an error to make this test meaningful');

        [$status, $result] = html\handle_embedded_data_url_images($config, $this->image(base64_encode('png-bytes')));

        $this->assertTrue($status, 'a foreign libxml error must not turn this into a failure');
        $this->assertStringContainsString('src="media/' . self::FILE . '"', (string) $result);
    }

    public function testGalleryImageIsLinkedToItsFullVersion(): void
    {
        $config = test_config();
        $html = '<html><body><img data-jaco-editable-image data-jaco-editable-image-gallery'
            . ' src="data:image/png;base64,' . base64_encode('thumb-bytes') . '"'
            . ' data-jaco-editable-image-file="' . self::GALLERY_FILE . '"'
            . ' data-jaco-gallery-full="data:image/png;base64,' . base64_encode('full-bytes') . '" alt="test"></body></html>';

        [$status, $result] = html\handle_embedded_data_url_images($config, $html);

        $this->assertTrue($status);
        $this->assertSame('thumb-bytes', file_get_contents($config->mediaDir . '/' . self::GALLERY_FILE));
        $this->assertSame('full-bytes', file_get_contents($config->mediaDir . '/' . self::GALLERY_FULL_FILE));

        $result = (string) $result;
        $this->assertStringContainsString('<a href="media/' . self::GALLERY_FULL_FILE . '">', $result);
        $this->assertStringContainsString('src="media/' . self::GALLERY_FILE . '"', $result);
        $this->assertStringContainsString('data-jaco-editable-image-gallery', $result);
        $this->assertStringNotContainsString('data-jaco-gallery-full', $result);
        $this->assertStringNotContainsString('base64', $result);
    }

    public function testGalleryWithoutFullVersionStaysPlain(): void
    {
        $config = test_config();
        $html = '<html><body><img data-jaco-editable-image data-jaco-editable-image-gallery'
            . ' src="data:image/png;base64,' . base64_encode('thumb-bytes') . '"'
            . ' data-jaco-editable-image-file="' . self::GALLERY_FILE . '" alt="test"></body></html>';

        [$status, $result] = html\handle_embedded_data_url_images($config, $html);

        $this->assertTrue($status);
        $this->assertSame('thumb-bytes', file_get_contents($config->mediaDir . '/' . self::GALLERY_FILE));
        $this->assertFileDoesNotExist($config->mediaDir . '/' . self::GALLERY_FULL_FILE);
        $this->assertStringNotContainsString('<a href=', (string) $result);
    }

    public function testGalleryKeepsAnExistingParentLink(): void
    {
        $config = test_config();
        $html = '<html><body><a href="/keep.html" data-jaco-editable-link>'
            . '<img data-jaco-editable-image data-jaco-editable-image-gallery'
            . ' src="data:image/png;base64,' . base64_encode('thumb-bytes') . '"'
            . ' data-jaco-editable-image-file="' . self::GALLERY_FILE . '"'
            . ' data-jaco-gallery-full="data:image/png;base64,' . base64_encode('full-bytes') . '" alt="test"></a></body></html>';

        [$status, $result] = html\handle_embedded_data_url_images($config, $html);

        $this->assertTrue($status);
        $this->assertStringContainsString('<a href="/keep.html"', (string) $result);
        $this->assertFileDoesNotExist($config->mediaDir . '/' . self::GALLERY_FULL_FILE);
    }

    public function testBrokenGalleryFullIsReported(): void
    {
        $config = test_config();
        $html = '<html><body><img data-jaco-editable-image data-jaco-editable-image-gallery'
            . ' src="data:image/png;base64,' . base64_encode('thumb-bytes') . '"'
            . ' data-jaco-editable-image-file="' . self::GALLERY_FILE . '"'
            . ' data-jaco-gallery-full="data:image/png;base64,not-base64!!" alt="test"></body></html>';

        [$status, $errors] = html\handle_embedded_data_url_images($config, $html);

        $this->assertFalse($status);
        $this->assertSame(['Could not decode image data: ' . self::GALLERY_FULL_FILE], $errors);
    }

    private function image(string $base64, string $fileName = self::FILE): string
    {
        return '<html><body><img data-jaco-editable-image src="data:image/png;base64,' . $base64 . '"'
            . ' data-jaco-editable-image-file="' . $fileName . '" alt="test"></body></html>';
    }
}

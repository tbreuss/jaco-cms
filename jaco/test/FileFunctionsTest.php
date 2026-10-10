<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\filesystem;

use const jaco\WEB_DIR;

/**
 * @testdox Filesystem Module
 */
final class FileFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (filesystem\dir_exists(WEB_DIR, 'test')) {
            filesystem\remove_dir_rec(WEB_DIR, 'test');
        }
        filesystem\make_dir_rec(WEB_DIR, 'test');
    }

    public function tearDown(): void
    {
        if (filesystem\dir_exists(WEB_DIR, 'test')) {
            filesystem\remove_dir_rec(WEB_DIR, 'test');
        }
    }

    public function testExistingFileCanBeChecked(): void
    {
        touch(WEB_DIR . '/test/foo.html');
        $this->assertTrue(filesystem\file_exists(WEB_DIR, 'test/foo.html'));
    }

    public function testNotExistingFileCanBeChecked(): void
    {
        $this->assertFalse(filesystem\file_exists(WEB_DIR, 'xxx.html'));
        $this->assertFalse(filesystem\file_exists(WEB_DIR, 'xxx/yyy/zzz.html'));
    }

    public function testWritableFileCanBeChecked(): void
    {
        filesystem\make_dir_rec(WEB_DIR, 'test/is_writeable');
        touch(WEB_DIR . '/test/is_writeable/a.html');
        $this->assertTrue(filesystem\file_is_writable(WEB_DIR, 'test/is_writeable/a.html'));
    }

    public function testNotWritableFileCanBeChecked(): void
    {
        filesystem\make_dir_rec(WEB_DIR, 'test/is_writeable');
        touch(WEB_DIR . '/test/is_writeable/a.html');
        filesystem\make_dir_rec(WEB_DIR, 'test/not_writeable', 0644);
        $this->assertFalse(filesystem\file_is_writable(WEB_DIR, 'test/foo/writeable.html'));

        // the not_writeable dir is not traversable, restore permissions and remove it
        chmod(WEB_DIR . '/test/not_writeable', 0755);
        filesystem\remove_dir(WEB_DIR, 'test/not_writeable');
    }

    public function testDirectoryCanBeCreated(): void
    {
        // make_dir is not recursive, so the parent has to exist
        filesystem\make_dir_rec(WEB_DIR, 'test/parent');
        filesystem\make_dir(WEB_DIR, 'test/parent/abc');
        $this->assertTrue(is_dir(WEB_DIR . '/test/parent/abc'));
    }

    public function testDirectoryCanBeCreatedRecursively(): void
    {
        filesystem\make_dir_rec(WEB_DIR, 'test/foo/bar/baz');
        $this->assertTrue(is_dir(WEB_DIR . '/test/foo'));
        $this->assertTrue(is_dir(WEB_DIR . '/test/foo/bar'));
        $this->assertTrue(is_dir(WEB_DIR . '/test/foo/bar/baz'));
    }
}

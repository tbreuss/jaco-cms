<?php

declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\filesystem;

use const jaco\WEB_DIR;

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

    public function testFileExistsWithExistingFile(): void
    {
        touch(WEB_DIR . '/test/foo.html');
        $this->assertTrue(filesystem\file_exists(WEB_DIR, 'test/foo.html'));
    }

    public function testFileExistsWithNotExistingFile(): void
    {
        $this->assertFalse(filesystem\file_exists(WEB_DIR, 'xxx.html'));
        $this->assertFalse(filesystem\file_exists(WEB_DIR, 'xxx/yyy/zzz.html'));
    }

    public function testIsWritableWithWritableFile(): void
    {
        filesystem\make_dir_rec(WEB_DIR, 'test/is_writeable');
        touch(WEB_DIR . '/test/is_writeable/a.html');
        $this->assertTrue(filesystem\file_is_writable(WEB_DIR, 'test/is_writeable/a.html'));
    }

    public function testIsWritableWithNotWritableFile(): void
    {
        filesystem\make_dir_rec(WEB_DIR, 'test/is_writeable');
        touch(WEB_DIR . '/test/is_writeable/a.html');
        filesystem\make_dir_rec(WEB_DIR, 'test/not_writeable', 0644);
        $this->assertFalse(filesystem\file_is_writable(WEB_DIR, 'test/foo/writeable.html'));
    }

    public function testMakeDir(): void
    {
        filesystem\make_dir(WEB_DIR, 'foo/abc');
        $this->assertTrue(is_dir(WEB_DIR . '/foo/abc'));
    }

    public function testMakeDirRecursive(): void
    {
        filesystem\make_dir_rec(WEB_DIR, 'foo/bar/baz');
        $this->assertTrue(is_dir(WEB_DIR . '/foo'));
        $this->assertTrue(is_dir(WEB_DIR . '/foo/bar'));
        $this->assertTrue(is_dir(WEB_DIR . '/foo/bar/baz'));
    }
}

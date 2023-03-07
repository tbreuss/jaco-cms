<?php

declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\filesystem;

final class FileFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (filesystem\dir_exists(JACO_WEB_DIR, 'test')) {
            filesystem\remove_dir_rec(JACO_WEB_DIR, 'test');
        }
        filesystem\make_dir_rec(JACO_WEB_DIR, 'test');
    }

    public function tearDown(): void
    {
        if (filesystem\dir_exists(JACO_WEB_DIR, 'test')) {
            filesystem\remove_dir_rec(JACO_WEB_DIR, 'test');
        }
    }

    public function testExistsReturnsTrueIfFileExists(): void
    {
        touch(JACO_WEB_DIR . '/test/foo.html');
        $this->assertTrue(filesystem\file_exists(JACO_WEB_DIR, 'test/foo.html'));
    }

    public function testExistsReturnsFalseIfFileNotExists(): void
    {
        $this->assertFalse(filesystem\file_exists(JACO_WEB_DIR, 'xxx.html'));
        $this->assertFalse(filesystem\file_exists(JACO_WEB_DIR, 'xxx/yyy/zzz.html'));
    }

    public function testIsWritableReturnsTrueIfFileIsWriteable(): void
    {
        filesystem\make_dir_rec(JACO_WEB_DIR, 'test/is_writeable');
        touch(JACO_WEB_DIR . '/test/is_writeable/a.html');
        $this->assertTrue(filesystem\file_is_writable(JACO_WEB_DIR, 'test/is_writeable/a.html'));
    }

    public function testIsWritableReturnsFalseIfFileIsNotWriteable(): void
    {
        filesystem\make_dir_rec(JACO_WEB_DIR, 'test/is_writeable');
        touch(JACO_WEB_DIR . '/test/is_writeable/a.html');
        filesystem\make_dir_rec(JACO_WEB_DIR, 'test/not_writeable', 0644);
        $this->assertFalse(filesystem\file_is_writable(JACO_WEB_DIR, 'test/foo/writeable.html'));
    }

    public function testMakeDir(): void
    {
        filesystem\make_dir(JACO_WEB_DIR, 'foo/abc');
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/abc'));
    }

    public function testMakeDirRec(): void
    {
        filesystem\make_dir_rec(JACO_WEB_DIR, 'foo/bar/baz');
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar/baz'));
    }
}

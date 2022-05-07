<?php

declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\filesystem\dir;
use jaco\filesystem\file;

final class FileFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (dir\exists(JACO_WEB_DIR, 'test')) {
            dir\remove_dir_recursive(JACO_WEB_DIR, 'test');
        }
        dir\make_dir_rec(JACO_WEB_DIR, 'test');
    }

    public function tearDown(): void
    {
        if (dir\exists(JACO_WEB_DIR, 'test')) {
            dir\remove_dir_recursive(JACO_WEB_DIR, 'test');
        }
    }

    public function testExistsReturnsTrueIfFileExists(): void
    {
        touch(JACO_WEB_DIR . '/test/foo.html');
        $this->assertTrue(file\exists(JACO_WEB_DIR, 'test/foo.html'));
    }

    public function testExistsReturnsFalseIfFileNotExists(): void
    {
        $this->assertFalse(file\exists(JACO_WEB_DIR, 'xxx.html'));
        $this->assertFalse(file\exists(JACO_WEB_DIR, 'xxx/yyy/zzz.html'));
    }

    public function testIsWritableReturnsTrueIfFileIsWriteable(): void
    {
        dir\make_dir_rec(JACO_WEB_DIR, 'test/is_writeable');
        touch(JACO_WEB_DIR . '/test/is_writeable/a.html');
        $this->assertTrue(file\is_writable(JACO_WEB_DIR, 'test/is_writeable/a.html'));
    }

    public function testIsWritableReturnsFalseIfFileIsNotWriteable(): void
    {
        dir\make_dir_rec(JACO_WEB_DIR, 'test/is_writeable');
        touch(JACO_WEB_DIR . '/test/is_writeable/a.html');
        dir\make_dir_rec(JACO_WEB_DIR, 'test/not_writeable', 0644);
        $this->assertFalse(file\is_writable(JACO_WEB_DIR, 'test/foo/writeable.html'));
    }

    public function testMakeDir(): void
    {
        dir\make_dir(JACO_WEB_DIR, 'foo/abc');
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/abc'));
    }

    public function testMakeDirRec(): void
    {
        dir\make_dir_rec(JACO_WEB_DIR, 'foo/bar/baz');
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar/baz'));
    }
}

<?php

declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\file;

final class FileFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (file\exists('test', JACO_WEB_DIR)) {
            file\remove_dir_recursive('test', JACO_WEB_DIR);
        }
        file\make_dir_rec('test');
    }

    public function tearDown(): void
    {
        if (file\exists('test', JACO_WEB_DIR)) {
            file\remove_dir_recursive('test', JACO_WEB_DIR);
        }
    }

    public function testExistsReturnsTrueIfFileExists(): void
    {
        touch(JACO_WEB_DIR . '/test/foo.html');
        $this->assertTrue(file\exists('test/foo.html', JACO_WEB_DIR));
    }

    public function testExistsReturnsFalseIfFileNotExists(): void
    {
        $this->assertFalse(file\exists('xxx.html', JACO_WEB_DIR));
        $this->assertFalse(file\exists('xxx/yyy/zzz.html', JACO_WEB_DIR));
    }

    public function testIsWritableReturnsTrueIfFileIsWriteable(): void
    {
        file\make_dir_rec('test/is_writeable');
        touch(JACO_WEB_DIR . '/test/is_writeable/a.html');
        $this->assertTrue(file\is_writable('test/is_writeable/a.html', JACO_WEB_DIR));
    }

    public function testIsWritableReturnsFalseIfFileIsNotWriteable(): void
    {
        file\make_dir_rec('test/is_writeable');
        touch(JACO_WEB_DIR . '/test/is_writeable/a.html');
        file\make_dir_rec('test/not_writeable', JACO_WEB_DIR, 0644);
        $this->assertFalse(file\is_writable('test/foo/writeable.html', JACO_WEB_DIR));
    }

    public function testMakeDir(): void
    {
        $dir = file\make_dir('foo/abc', JACO_WEB_DIR);
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/abc'));
    }

    public function testMakeDirRec(): void
    {
        $dir = file\make_dir_rec('foo/bar/baz', JACO_WEB_DIR);
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar/baz'));
    }
}

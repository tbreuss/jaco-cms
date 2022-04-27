<?php

declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\file;

final class FileFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (file\exists('foo', JACO_WEB_DIR)) {
            file\remove_dir_recursive('foo', JACO_WEB_DIR);
        }
    }

    public function tearDown(): void
    {
        if (file\exists('foo', JACO_WEB_DIR)) {
            file\remove_dir_recursive('foo', JACO_WEB_DIR);
        }
    }

    public function testExistsReturnsTrueIfFileExists(): void
    {
        touch(JACO_WEB_DIR . '/foo.html');
        $this->assertTrue(file\exists('foo.html', JACO_WEB_DIR));
    }

    public function testExistsReturnsFalseIfFileNotExists(): void
    {
        $this->assertFalse(file\exists('xxx.html', JACO_WEB_DIR));
        $this->assertFalse(file\exists('xxx/yyy/zzz.html', JACO_WEB_DIR));
    }

    public function testMakeDir(): void
    {
        $dir = file\make_dir('foo', JACO_WEB_DIR);
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo'));
    }

    public function testMakeDirRec(): void
    {
        $dir = file\make_dir_rec('foo/bar/baz', JACO_WEB_DIR);
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar'));
        $this->assertTrue(is_dir(JACO_WEB_DIR . '/foo/bar/baz'));
    }
}

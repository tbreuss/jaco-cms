<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\filesystem;

#[TestDox('Filesystem Module')]
final class FileFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        $config = test_config();
        if (filesystem\dir_exists($config->webDir, 'test')) {
            filesystem\remove_dir_rec($config->webDir, 'test');
        }
        filesystem\make_dir_rec($config, $config->webDir, 'test');
    }

    public function tearDown(): void
    {
        $config = test_config();
        if (filesystem\dir_exists($config->webDir, 'test')) {
            filesystem\remove_dir_rec($config->webDir, 'test');
        }
    }

    public function testExistingFileCanBeChecked(): void
    {
        $config = test_config();
        touch($config->webDir . '/test/foo.html');
        $this->assertTrue(filesystem\file_exists($config->webDir, 'test/foo.html'));
    }

    public function testNotExistingFileCanBeChecked(): void
    {
        $config = test_config();
        $this->assertFalse(filesystem\file_exists($config->webDir, 'xxx.html'));
        $this->assertFalse(filesystem\file_exists($config->webDir, 'xxx/yyy/zzz.html'));
    }

    public function testWritableFileCanBeChecked(): void
    {
        $config = test_config();
        filesystem\make_dir_rec($config, $config->webDir, 'test/is_writeable');
        touch($config->webDir . '/test/is_writeable/a.html');
        $this->assertTrue(filesystem\is_file_writable($config->webDir, 'test/is_writeable/a.html'));
    }

    public function testNotWritableFileCanBeChecked(): void
    {
        $config = test_config();
        filesystem\make_dir_rec($config, $config->webDir, 'test/is_writeable');
        touch($config->webDir . '/test/is_writeable/a.html');
        filesystem\make_dir_rec($config, $config->webDir, 'test/not_writeable', 0644);
        $this->assertFalse(filesystem\is_file_writable($config->webDir, 'test/foo/writeable.html'));

        // the not_writeable dir is not traversable, restore permissions and remove it
        chmod($config->webDir . '/test/not_writeable', 0755);
        filesystem\remove_dir($config->webDir, 'test/not_writeable');
    }

    public function testDirectoryCanBeCreated(): void
    {
        $config = test_config();
        // make_dir is not recursive, so the parent has to exist
        filesystem\make_dir_rec($config, $config->webDir, 'test/parent');
        filesystem\make_dir($config, $config->webDir, 'test/parent/abc');
        $this->assertTrue(is_dir($config->webDir . '/test/parent/abc'));
    }

    public function testDirectoryCanBeCreatedRecursively(): void
    {
        $config = test_config();
        filesystem\make_dir_rec($config, $config->webDir, 'test/foo/bar/baz');
        $this->assertTrue(is_dir($config->webDir . '/test/foo'));
        $this->assertTrue(is_dir($config->webDir . '/test/foo/bar'));
        $this->assertTrue(is_dir($config->webDir . '/test/foo/bar/baz'));
    }
}

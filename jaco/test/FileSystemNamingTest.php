<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\filesystem;

#[TestDox('File naming and path handling')]
final class FileSystemNamingTest extends TestCase
{
    private const DIR = 'naming-test';
    private const PLAIN = 'naming-plain.html';

    protected function setUp(): void
    {
        $config = test_config();
        filesystem\make_dir_rec($config, $config->webDir, self::DIR);
    }

    protected function tearDown(): void
    {
        $config = test_config();
        @unlink($config->webDir . '/' . self::PLAIN);
        @unlink($config->webDir . '/naming-plain-1.html');
        @unlink($config->webDir . '/naming-plain-2.html');
        filesystem\remove_dir_rec($config->webDir, self::DIR);
    }

    public function testCombineJoinsWithASingleSeparator(): void
    {
        $this->assertSame('a/b', filesystem\combine('a/', '/b'));
        $this->assertSame('a/b', filesystem\combine('a', 'b'));
    }

    public function testFileNamesGetANumberWhenTheyAlreadyExist(): void
    {
        $config = test_config();
        // this is the supported case, all callers pass plain file names
        $this->assertSame(self::PLAIN, filesystem\file_name_to_write($config->webDir, self::PLAIN));

        filesystem\write_file($config, $config->webDir, self::PLAIN, 'a');
        $this->assertSame('naming-plain-1.html', filesystem\file_name_to_write($config->webDir, self::PLAIN));

        filesystem\write_file($config, $config->webDir, 'naming-plain-1.html', 'b');
        $this->assertSame('naming-plain-2.html', filesystem\file_name_to_write($config->webDir, self::PLAIN));
    }

    public function testCollisionKeepsTheDirectoryPrefix(): void
    {
        $config = test_config();
        filesystem\write_file($config, $config->webDir, self::DIR . '/x.html', 'a');

        $this->assertSame(
            self::DIR . '/x-1.html',
            filesystem\file_name_to_write($config->webDir, self::DIR . '/x.html'),
        );

        filesystem\write_file($config, $config->webDir, self::DIR . '/x-1.html', 'b');

        // the next check has to look into the same directory again
        $this->assertSame(
            self::DIR . '/x-2.html',
            filesystem\file_name_to_write($config->webDir, self::DIR . '/x.html'),
        );
    }

    public function testFileNameWithoutExtensionGetsANumber(): void
    {
        $config = test_config();
        filesystem\write_file($config, $config->webDir, self::DIR . '/noext', 'a');

        $this->assertSame(self::DIR . '/noext-1', filesystem\file_name_to_write($config->webDir, self::DIR . '/noext'));

        filesystem\write_file($config, $config->webDir, self::DIR . '/noext-1', 'b');

        $this->assertSame(self::DIR . '/noext-2', filesystem\file_name_to_write($config->webDir, self::DIR . '/noext'));
    }

    public function testValidateFilenameAllowsSubdirectoriesButNoSpaces(): void
    {
        $this->assertTrue(filesystem\validate_filename('x.html'));
        $this->assertTrue(filesystem\validate_filename('sub/x.html'));
        $this->assertFalse(filesystem\validate_filename('x y.html'));
        $this->assertFalse(filesystem\validate_filename(''));
    }

    public function testValidateFilenameAcceptsThePipeOnlyInBackupNames(): void
    {
        $backup = '20200102_030405_abcd_sub|page.html';

        $this->assertTrue(filesystem\validate_filename($backup, true));
        $this->assertFalse(filesystem\validate_filename($backup));
    }

    public function testValidateExtensionOnlyLooksAtTheExtension(): void
    {
        $config = test_config();
        $this->assertTrue(filesystem\validate_extension('x.html', $config->pageExtensions));
        $this->assertFalse(filesystem\validate_extension('x.txt', $config->pageExtensions));
        $this->assertFalse(filesystem\validate_extension('noextension', $config->pageExtensions));
        $this->assertTrue(filesystem\validate_extension('upload/x.jpg', ['jpg']));
    }

    public function testFileInDirComparesTheFirstSegmentOnly(): void
    {
        $this->assertTrue(filesystem\is_file_in_dir('jaco/x.html', 'jaco'));
        $this->assertFalse(filesystem\is_file_in_dir('jaco.html', 'jaco'));
        $this->assertFalse(filesystem\is_file_in_dir('x/jaco.html', 'jaco'));
    }

    public function testFileInDirIgnoresCase(): void
    {
        // on macOS and Windows "Jaco/..." resolves to the app directory
        $this->assertTrue(filesystem\is_file_in_dir('Jaco/x.html', 'jaco'));
        $this->assertTrue(filesystem\is_file_in_dir('JACO/x.html', 'jaco'));
    }

    public function testTimestampIsReadFromTheBackupName(): void
    {
        $this->assertSame(
            strtotime('2020-01-02 03:04:05'),
            filesystem\restore_timestamp_from_file_name('20200102_030405_abcd_sub|page.html'),
        );
    }
}

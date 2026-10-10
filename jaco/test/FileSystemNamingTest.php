<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\filesystem;

use const jaco\WEB_DIR;

/**
 * @testdox File naming and path handling
 */
final class FileSystemNamingTest extends TestCase
{
    private const DIR = 'naming-test';
    private const PLAIN = 'naming-plain.html';

    protected function setUp(): void
    {
        filesystem\make_dir_rec(WEB_DIR, self::DIR);
    }

    protected function tearDown(): void
    {
        @unlink(WEB_DIR . '/' . self::PLAIN);
        @unlink(WEB_DIR . '/naming-plain-1.html');
        @unlink(WEB_DIR . '/naming-plain-2.html');
        filesystem\remove_dir_rec(WEB_DIR, self::DIR);
    }

    public function testCombineJoinsWithASingleSeparator(): void
    {
        $this->assertSame('a/b', filesystem\combine('a/', '/b'));
        $this->assertSame('a/b', filesystem\combine('a', 'b'));
    }

    public function testFileNamesGetANumberWhenTheyAlreadyExist(): void
    {
        // this is the supported case, all callers pass plain file names
        $this->assertSame(self::PLAIN, filesystem\file_name_to_write(WEB_DIR, self::PLAIN));

        filesystem\write_file(WEB_DIR, self::PLAIN, 'a');
        $this->assertSame('naming-plain-1.html', filesystem\file_name_to_write(WEB_DIR, self::PLAIN));

        filesystem\write_file(WEB_DIR, 'naming-plain-1.html', 'b');
        $this->assertSame('naming-plain-2.html', filesystem\file_name_to_write(WEB_DIR, self::PLAIN));
    }

    public function testCollisionKeepsTheDirectoryPrefix(): void
    {
        filesystem\write_file(WEB_DIR, self::DIR . '/x.html', 'a');

        $this->assertSame(
            self::DIR . '/x-1.html',
            filesystem\file_name_to_write(WEB_DIR, self::DIR . '/x.html'),
        );

        filesystem\write_file(WEB_DIR, self::DIR . '/x-1.html', 'b');

        // the next check has to look into the same directory again
        $this->assertSame(
            self::DIR . '/x-2.html',
            filesystem\file_name_to_write(WEB_DIR, self::DIR . '/x.html'),
        );
    }

    public function testFileNameWithoutExtensionGetsANumber(): void
    {
        filesystem\write_file(WEB_DIR, self::DIR . '/noext', 'a');

        $this->assertSame(self::DIR . '/noext-1', filesystem\file_name_to_write(WEB_DIR, self::DIR . '/noext'));

        filesystem\write_file(WEB_DIR, self::DIR . '/noext-1', 'b');

        $this->assertSame(self::DIR . '/noext-2', filesystem\file_name_to_write(WEB_DIR, self::DIR . '/noext'));
    }

    public function testValidateFilenameAllowsSubdirectoriesButNoSpaces(): void
    {
        $this->assertTrue(filesystem\validate_filename(WEB_DIR, 'x.html'));
        $this->assertTrue(filesystem\validate_filename(WEB_DIR, 'sub/x.html'));
        $this->assertFalse(filesystem\validate_filename(WEB_DIR, 'x y.html'));
        $this->assertFalse(filesystem\validate_filename(WEB_DIR, ''));
    }

    public function testValidateFilenameAcceptsThePipeOnlyInBackupNames(): void
    {
        $backup = '20200102_030405_abcd_sub|page.html';

        $this->assertTrue(filesystem\validate_filename(WEB_DIR, $backup, true));
        $this->assertFalse(filesystem\validate_filename(WEB_DIR, $backup));
    }

    public function testValidateExtensionOnlyLooksAtTheExtension(): void
    {
        $this->assertTrue(filesystem\validate_extension(WEB_DIR, 'x.html'));
        $this->assertFalse(filesystem\validate_extension(WEB_DIR, 'x.txt'));
        $this->assertFalse(filesystem\validate_extension(WEB_DIR, 'noextension'));
        $this->assertTrue(filesystem\validate_extension(WEB_DIR, 'upload/x.jpg', ['jpg']));
    }

    public function testFileInDirComparesTheFirstSegmentOnly(): void
    {
        $this->assertTrue(filesystem\file_in_dir('jaco/x.html', 'jaco'));
        $this->assertFalse(filesystem\file_in_dir('jaco.html', 'jaco'));
        $this->assertFalse(filesystem\file_in_dir('x/jaco.html', 'jaco'));
    }

    public function testTimestampIsReadFromTheBackupName(): void
    {
        $this->assertSame(
            strtotime('2020-01-02 03:04:05'),
            filesystem\restore_timestamp_from_file_name('20200102_030405_abcd_sub|page.html'),
        );
    }
}

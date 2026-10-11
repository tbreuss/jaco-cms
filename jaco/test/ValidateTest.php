<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\data;

/**
 * @testdox Input validation rules
 */
final class ValidateTest extends TestCase
{
    public function testRequiredRule(): void
    {
        $this->assertSame([['file', 'Value cannot be blank.']], data\validate_one('', 'file', [['required']]));
        $this->assertSame([], data\validate_one('x.html', 'file', [['required']]));
        $this->assertSame([], data\validate_one(0, 'file', [['required']]));
    }

    public function testStringAndNumericRules(): void
    {
        $this->assertSame(
            [['s', 'ab is too short, minimum is 3 characters.']],
            data\validate(['s' => 'ab'], [['s', 'string', 'min' => 3]]),
        );
        $this->assertSame(
            [['s', 'abcd is too long, maximum is 3 characters.']],
            data\validate(['s' => 'abcd'], [['s', 'string', 'max' => 3]]),
        );
        $this->assertSame([['n', 'x must be a number.']], data\validate(['n' => 'x'], [['n', 'numeric']]));
    }

    public function testOnlyTheFirstFailurePerFieldIsReported(): void
    {
        $config = test_config();
        $errors = data\validate(['file' => ''], [
            ['file', 'required'],
            ['file', 'html_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('Value cannot be blank.', $errors[0][1]);
    }

    public function testExtensionRule(): void
    {
        $allowed = [['f', 'file_extension', 'allowedExtensions' => ['png']]];

        $this->assertSame([], data\validate(['f' => 'x.png'], $allowed));
        $this->assertSame([['f', 'x.txt is an invalid extension']], data\validate(['f' => 'x.txt'], $allowed));
    }

    public function testFileExistsRuleWithAndWithoutNegation(): void
    {
        $config = test_config();
        // foo.html is part of the test fixtures
        $this->assertSame([], data\validate(['f' => 'foo.html'], [['f', 'file_exists', 'dir' => $config->webDir]]));
        $this->assertSame(
            [['f', 'foo.html must not exist.']],
            data\validate(['f' => 'foo.html'], [['f', 'file_exists', 'dir' => $config->webDir, 'negate' => true]]),
        );
        $this->assertSame(
            [['f', 'nope.html does not exist.']],
            data\validate(['f' => 'nope.html'], [['f', 'file_exists', 'dir' => $config->webDir]]),
        );
        $this->assertSame([], data\validate(['f' => 'nope.html'], [['f', 'file_exists', 'dir' => $config->webDir, 'negate' => true]]));
    }

    public function testParentSegmentsAreRejected(): void
    {
        $config = test_config();
        $this->assertSame(
            [['f', '../foo.html does not exist.']],
            data\validate(['f' => '../foo.html'], [['f', 'file_exists', 'dir' => $config->webDir]]),
        );
        $this->assertSame(
            [['f', '../new.html must not exist.']],
            data\validate(['f' => '../new.html'], [['f', 'file_exists', 'dir' => $config->webDir, 'negate' => true]]),
        );
        $this->assertSame(
            [['f', '../evil.html is an invalid filename']],
            data\validate(['f' => '../evil.html'], [['f', 'html_file_name']]),
        );
    }

    public function testFileInDirRuleGuardsTheAppDirectory(): void
    {
        $this->assertSame([], data\validate(['f' => 'jaco/x.html'], [['f', 'file_in_dir', 'dir' => 'jaco']]));
        $this->assertSame(
            [['f', 'jaco/x.html must not be in the jaco folder.']],
            data\validate(['f' => 'jaco/x.html'], [['f', 'file_in_dir', 'dir' => 'jaco', 'negate' => true]]),
        );
        $this->assertSame([], data\validate(['f' => 'x.html'], [['f', 'file_in_dir', 'dir' => 'jaco', 'negate' => true]]));
    }

    public function testSortParameterRuleAcceptsOnlyKnownFields(): void
    {
        $fields = ['created', 'file'];

        $this->assertSame([], data\validate(['s' => 'file'], [['s', 'sort_parameter', 'fields' => $fields]]));
        $this->assertSame([], data\validate(['s' => '-created'], [['s', 'sort_parameter', 'fields' => $fields]]));
        $this->assertSame(
            [['s', 'nope is an invalid sort parameter.']],
            data\validate(['s' => 'nope'], [['s', 'sort_parameter', 'fields' => $fields]]),
        );
    }

    public function testSafeRuleAcceptsEverything(): void
    {
        // "safe" is the explicit opt out of validation
        $this->assertSame([], data\validate(['f' => 'anything'], [['f', 'safe']]));
        $this->assertSame([], data\validate_one('', 'file', [['safe']]));
    }

    public function testWebDirIsTheDefaultDirectoryForFileRules(): void
    {
        $config = test_config();
        $this->assertFileExists($config->webDir . '/foo.html');
        $this->assertSame([], data\validate(['f' => 'foo.html'], [['f', 'file_exists', 'dir' => $config->webDir]]));
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use jaco\core\data;

#[TestDox('Input validation rules')]
final class ValidateTest extends TestCase
{
    public function testRequiredRule(): void
    {
        $this->assertSame([['file', 'Value cannot be blank.']], data\validate_value('', 'file', [['is_required']]));
        $this->assertSame([], data\validate_value('x.html', 'file', [['is_required']]));
        $this->assertSame([], data\validate_value(0, 'file', [['is_required']]));
    }

    public function testStringAndNumericRules(): void
    {
        $this->assertSame(
            [['s', 'ab is too short, minimum is 3 characters.']],
            data\validate(['s' => 'ab'], [['s', 'is_string', 'min' => 3]]),
        );
        $this->assertSame(
            [['s', 'abcd is too long, maximum is 3 characters.']],
            data\validate(['s' => 'abcd'], [['s', 'is_string', 'max' => 3]]),
        );
        $this->assertSame([['n', 'x must be a number.']], data\validate(['n' => 'x'], [['n', 'is_numeric']]));
    }

    public function testOnlyTheFirstFailurePerFieldIsReported(): void
    {
        $config = test_config();
        $errors = data\validate(['file' => ''], [
            ['file', 'is_required'],
            ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('Value cannot be blank.', $errors[0][1]);
    }

    public function testExtensionRule(): void
    {
        $allowed = [['f', 'has_file_extension', 'allowedExtensions' => ['png']]];

        $this->assertSame([], data\validate(['f' => 'x.png'], $allowed));
        $this->assertSame([['f', 'x.txt is an invalid extension']], data\validate(['f' => 'x.txt'], $allowed));
    }

    public function testFileExistsRuleWithAndWithoutNegation(): void
    {
        $config = test_config();
        // foo.html is part of the test fixtures
        $this->assertSame([], data\validate(['f' => 'foo.html'], [['f', 'is_file_present', 'dir' => $config->webDir]]));
        $this->assertSame(
            [['f', 'foo.html must not exist.']],
            data\validate(['f' => 'foo.html'], [['f', 'is_file_present', 'dir' => $config->webDir, 'negate' => true]]),
        );
        $this->assertSame(
            [['f', 'nope.html does not exist.']],
            data\validate(['f' => 'nope.html'], [['f', 'is_file_present', 'dir' => $config->webDir]]),
        );
        $this->assertSame([], data\validate(['f' => 'nope.html'], [['f', 'is_file_present', 'dir' => $config->webDir, 'negate' => true]]));
    }

    public function testParentSegmentsAreRejected(): void
    {
        $config = test_config();
        $this->assertSame(
            [['f', '../foo.html does not exist.']],
            data\validate(['f' => '../foo.html'], [['f', 'is_file_present', 'dir' => $config->webDir]]),
        );
        $this->assertSame(
            [['f', '../new.html must not exist.']],
            data\validate(['f' => '../new.html'], [['f', 'is_file_present', 'dir' => $config->webDir, 'negate' => true]]),
        );
        $this->assertSame(
            [['f', '../evil.html is an invalid filename']],
            data\validate(['f' => '../evil.html'], [['f', 'has_html_file_name']]),
        );
    }

    public function testFileInDirRuleGuardsTheAppDirectory(): void
    {
        $this->assertSame([], data\validate(['f' => 'jaco/x.html'], [['f', 'is_file_in_dir', 'dir' => 'jaco']]));
        $this->assertSame(
            [['f', 'jaco/x.html must not be in the jaco folder.']],
            data\validate(['f' => 'jaco/x.html'], [['f', 'is_file_in_dir', 'dir' => 'jaco', 'negate' => true]]),
        );
        $this->assertSame([], data\validate(['f' => 'x.html'], [['f', 'is_file_in_dir', 'dir' => 'jaco', 'negate' => true]]));
    }

    public function testSortParameterRuleAcceptsOnlyKnownFields(): void
    {
        $fields = ['created', 'file'];

        $this->assertSame([], data\validate(['s' => 'file'], [['s', 'is_sort_parameter', 'fields' => $fields]]));
        $this->assertSame([], data\validate(['s' => '-created'], [['s', 'is_sort_parameter', 'fields' => $fields]]));
        $this->assertSame(
            [['s', 'nope is an invalid sort parameter.']],
            data\validate(['s' => 'nope'], [['s', 'is_sort_parameter', 'fields' => $fields]]),
        );
    }

    public function testSafeRuleAcceptsEverything(): void
    {
        // "safe" is the explicit opt out of validation
        $this->assertSame([], data\validate(['f' => 'anything'], [['f', 'is_safe']]));
        $this->assertSame([], data\validate_value('', 'file', [['is_safe']]));
    }

    public function testUnknownValidatorIsRejectedWithItsName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown validator: filed_exists');

        data\validate(['f' => 'a.html'], [['f', 'filed_exists']]);
    }

    public function testWebDirIsTheDefaultDirectoryForFileRules(): void
    {
        $config = test_config();
        $this->assertFileExists($config->webDir . '/foo.html');
        $this->assertSame([], data\validate(['f' => 'foo.html'], [['f', 'is_file_present', 'dir' => $config->webDir]]));
    }
}

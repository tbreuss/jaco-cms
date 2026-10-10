<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\json\schema;

/**
 * @testdox JSON schema validation
 */
final class SchemaValidationTest extends TestCase
{
    public function testValidRequestDataPasses(): void
    {
        $this->assertSame([], schema\validate_data(['username' => 'a', 'password' => 'b'], 'login/request.json'));
    }

    public function testMissingRequiredPropertyIsReported(): void
    {
        $errors = schema\validate_data(['username' => 'a'], 'login/request.json');

        $this->assertCount(1, $errors);
        $this->assertSame('Invalid data', $errors[0]['title']);
        $this->assertStringContainsString('password', $errors[0]['detail']);
    }

    public function testWrongTypePointsAtTheProperty(): void
    {
        $errors = schema\validate_data(['username' => 123, 'password' => 'b'], 'login/request.json');

        $this->assertCount(1, $errors);
        $this->assertSame('username', $errors[0]['source']['pointer']);
        $this->assertStringContainsString('string', $errors[0]['detail']);
    }

    public function testAdditionalPropertiesAreRejected(): void
    {
        $errors = schema\validate_data(
            ['username' => 'a', 'password' => 'b', 'extra' => 1],
            'login/request.json',
        );

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('extra', $errors[0]['detail']);
    }

    public function testPageUpdateSchemaRequiresHtml(): void
    {
        $this->assertSame(
            [],
            schema\validate_data(['fileName' => 'x.html', 'html' => '<p>x</p>'], 'page/update/request.json'),
        );

        $errors = schema\validate_data(['fileName' => 'x.html'], 'page/update/request.json');

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('html', $errors[0]['detail']);
    }

    public function testNullIsNotAValidRequest(): void
    {
        $this->assertNotEmpty(schema\validate_data(null, 'login/request.json'));
    }
}

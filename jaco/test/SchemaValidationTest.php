<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\json;

#[TestDox('JSON schema validation')]
final class SchemaValidationTest extends TestCase
{
    public function testValidRequestDataPasses(): void
    {
        $this->assertSame([], json\validate_data(['username' => 'a', 'password' => 'b'], 'login/request.json'));
    }

    public function testMissingRequiredPropertyIsReported(): void
    {
        $errors = json\validate_data(['username' => 'a'], 'login/request.json');

        $this->assertCount(1, $errors);
        $this->assertSame('Invalid data', $errors[0]['title']);
        $this->assertStringContainsString('password', $errors[0]['detail']);
    }

    public function testWrongTypePointsAtTheProperty(): void
    {
        $errors = json\validate_data(['username' => 123, 'password' => 'b'], 'login/request.json');

        $this->assertCount(1, $errors);
        $this->assertSame('username', $errors[0]['source']['pointer']);
        $this->assertStringContainsString('string', $errors[0]['detail']);
    }

    public function testAdditionalPropertiesAreRejected(): void
    {
        $errors = json\validate_data(
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
            json\validate_data(['fileName' => 'x.html', 'html' => '<p>x</p>'], 'page/update/request.json'),
        );

        $errors = json\validate_data(['fileName' => 'x.html'], 'page/update/request.json');

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('html', $errors[0]['detail']);
    }

    public function testNullIsNotAValidRequest(): void
    {
        $this->assertNotEmpty(json\validate_data(null, 'login/request.json'));
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\system;

#[TestDox('Defined constants')]
final class DefinedConstantsTest extends TestCase
{
    public function testConfigValuesAreListedWithSecretsMasked(): void
    {
        $config = test_config();
        $constants = system\defined_constants($config);

        $this->assertSame('****', $constants['config.secretKey']);
        $this->assertSame('****', $constants['config.password']);
        $this->assertSame('test_user', $constants['config.username']);
        $this->assertSame('/jaco/backup', $constants['config.backupDir']);

        foreach ($constants as $value) {
            $this->assertStringNotContainsString('test_1234', (string) $value);
            $this->assertStringNotContainsString('test_pass', (string) $value);
        }
    }
}

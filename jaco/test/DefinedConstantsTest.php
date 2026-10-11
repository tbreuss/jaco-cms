<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\system;
use jaco\domain\system as domain;

#[TestDox('Defined constants')]
final class DefinedConstantsTest extends TestCase
{
    public function testConfigValuesAreListedWithSecretsMasked(): void
    {
        $config = test_config();
        $environment = domain\get_environment($config);
        $configValues = $environment->data['config'];

        $this->assertSame('********', $configValues['secretKey']);
        $this->assertSame('********', $configValues['password']);
        $this->assertSame('test_user', $configValues['username']);
        $this->assertSame(str_replace('\\', '/', $config->backupDir), $configValues['backupDir']);

        foreach ($configValues as $value) {
            $string = is_array($value) ? json_encode($value) : (string) $value;
            $this->assertStringNotContainsString('test_1234', $string);
            $this->assertStringNotContainsString('test_pass', $string);
        }
    }

    public function testConfigValuesAreNotInConstants(): void
    {
        $config = test_config();
        $constants = system\defined_constants($config);

        $this->assertArrayNotHasKey('config.secretKey', $constants);
        $this->assertArrayNotHasKey('config.password', $constants);
        $this->assertArrayNotHasKey('config.username', $constants);
    }
}

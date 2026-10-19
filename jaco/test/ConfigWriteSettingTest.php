<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function jaco\core\config\write_setting;

/**
 * write_setting() edits one value in an existing settings.php. The tests work on a
 * throw away file, never on the real one: a broken settings file locks the admin out.
 */
#[TestDox('Write a single setting')]
final class ConfigWriteSettingTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/jaco-settings-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/settings.php');
        @rmdir($this->dir);
    }

    private function writeSource(string $source): void
    {
        file_put_contents($this->dir . '/settings.php', $source);
    }

    private function readSource(): string
    {
        return (string) file_get_contents($this->dir . '/settings.php');
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return (array) include $this->dir . '/settings.php';
    }

    public function testReplacesAStringAndKeepsTheComments(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            // keep me, I explain the secret below
            return [
                'username' => 'demo',
                // the digest lives here
                'password' => 'old',
                'secretKey' => 'abc',
            ];
            PHP);

        $this->assertTrue(write_setting($this->dir, 'password', 'new-digest'));

        $source = $this->readSource();
        $this->assertStringContainsString('// keep me, I explain the secret below', $source);
        $this->assertStringContainsString('// the digest lives here', $source);
        $this->assertSame('new-digest', $this->settings()['password']);
        $this->assertSame('demo', $this->settings()['username']);
        $this->assertSame('abc', $this->settings()['secretKey']);
    }

    public function testReplacesABooleanWithABooleanLiteral(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            return [
                'maintenanceMode' => false,
            ];
            PHP);

        $this->assertTrue(write_setting($this->dir, 'maintenanceMode', true));

        $this->assertTrue($this->settings()['maintenanceMode']);
        $this->assertStringContainsString("'maintenanceMode' => true,", $this->readSource());
    }

    public function testReplacesAnInteger(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            return [
                'backupRetentionDays' => 90,
            ];
            PHP);

        $this->assertTrue(write_setting($this->dir, 'backupRetentionDays', 30));

        $this->assertSame(30, $this->settings()['backupRetentionDays']);
    }

    public function testAppendsAMissingKey(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            return [
                'username' => 'demo',
            ];
            PHP);

        $this->assertTrue(write_setting($this->dir, 'maintenanceMode', true));

        $settings = $this->settings();
        $this->assertTrue($settings['maintenanceMode']);
        $this->assertSame('demo', $settings['username']);
        // the new entry sits before the closing bracket, whose line ending is kept
        $this->assertStringEndsWith(PHP_EOL . '];', $this->readSource());
    }

    public function testEscapesStringsWithQuotesAndBackslashes(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            return [
                'password' => 'old',
            ];
            PHP);

        $value = "pa'ss\\word";

        $this->assertTrue(write_setting($this->dir, 'password', $value));

        $this->assertSame($value, $this->settings()['password']);
    }

    public function testFailsWithoutAFileAndCreatesNothing(): void
    {
        $this->assertFalse(write_setting($this->dir, 'password', 'x'));
        $this->assertFileDoesNotExist($this->dir . '/settings.php');
    }

    public function testFailsForAValueItCannotReplaceLineWise(): void
    {
        $source = <<<'PHP'
            <?php declare(strict_types=1);

            return [
                'uploadExtensions' => [
                    'png',
                    'jpg',
                ],
            ];
            PHP;
        $this->writeSource($source);

        $this->assertFalse(write_setting($this->dir, 'uploadExtensions', 'png'));
        $this->assertSame($source, $this->readSource(), 'the file is left untouched');
    }
}

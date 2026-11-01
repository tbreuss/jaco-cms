<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function jaco\core\config\write_user_field;

/**
 * write_user_field() edits one field of one user in an existing settings.php and
 * leaves the rest of the file alone. The tests work on a throw away file, never on
 * the real one: a broken settings file locks the admin out.
 */
#[TestDox('Write one field of one user')]
final class ConfigWriteUserFieldTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/jaco-user-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0755, true);
        mkdir($this->dir . '/jaco-data', 0755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->settingsPath());
        @rmdir($this->dir . '/jaco-data');
        @rmdir($this->dir);
    }

    private function settingsPath(): string
    {
        return $this->dir . '/jaco-data/settings.php';
    }

    private function writeSource(string $source): void
    {
        file_put_contents($this->settingsPath(), $source);
    }

    private function readSource(): string
    {
        return (string) file_get_contents($this->settingsPath());
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return (array) include $this->settingsPath();
    }

    public function testReplacesOneFieldAndKeepsTheRest(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            // keep me, other accounts live here too
            return [
                'users' => [
                    'admin' => ['password' => 'admin-hash', 'role' => 'admin'],
                    'editor' => ['password' => 'editor-hash', 'role' => 'editor', 'language' => 'en'],
                ],
            ];
            PHP);

        $this->assertTrue(write_user_field($this->settingsPath(), 'admin', 'language', 'de'));

        $settings = $this->settings();
        $this->assertSame('de', $settings['users']['admin']['language']);
        $this->assertSame('admin-hash', $settings['users']['admin']['password']);
        $this->assertSame('en', $settings['users']['editor']['language'], 'the other account is untouched');
        // the file is managed: a note at the top replaces the comments it had,
        // and points at the template that explains the keys
        $source = $this->readSource();
        $this->assertStringNotContainsString('// keep me, other accounts live here too', $source);
        $this->assertStringContainsString('Managed by Jaco', $source);
        $this->assertStringContainsString('settings.dist.php', $source);
    }

    public function testKeepsWorkingAfterItRewroteTheFile(): void
    {
        // regression: the file used to be patched in place, so the first edit left
        // var_export()'s long array (...) syntax behind and the second one failed to
        // find the block again. The file is read and written as a whole now, and
        // the second write has to work just as well as the first
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            return [
                'users' => [
                    'admin' => ['password' => 'admin-hash', 'role' => 'admin'],
                ],
            ];
            PHP);

        $this->assertTrue(write_user_field($this->settingsPath(), 'admin', 'language', 'de'));
        $this->assertTrue(write_user_field($this->settingsPath(), 'admin', 'language', 'fr'));

        $this->assertSame('fr', $this->settings()['users']['admin']['language']);
    }

    public function testRemovesAFieldWithNull(): void
    {
        $this->writeSource(<<<'PHP'
            <?php declare(strict_types=1);

            return [
                'users' => [
                    'admin' => ['password' => 'admin-hash', 'role' => 'admin', 'totpSecret' => 'SECRET'],
                ],
            ];
            PHP);

        $this->assertTrue(write_user_field($this->settingsPath(), 'admin', 'totpSecret', null));

        $this->assertArrayNotHasKey('totpSecret', $this->settings()['users']['admin']);
    }

    public function testFailsForAnUnknownUserAndLeavesTheFileAlone(): void
    {
        $source = <<<'PHP'
            <?php declare(strict_types=1);

            return [
                'users' => [
                    'admin' => ['password' => 'admin-hash', 'role' => 'admin'],
                ],
            ];
            PHP;
        $this->writeSource($source);

        $this->assertFalse(write_user_field($this->settingsPath(), 'ghost', 'language', 'de'));
        $this->assertSame($source, $this->readSource());
    }
}

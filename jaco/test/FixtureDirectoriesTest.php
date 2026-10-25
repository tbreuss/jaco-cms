<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * The tests write into fixed directories below jaco/test/asset/, and git keeps an
 * empty directory only through a .gitignore inside it. A missing placeholder is
 * invisible in a working copy that still has the directory, but breaks a fresh
 * clone - that is how nine tests failed once the data directory moved.
 */
#[TestDox('Test fixture directories are part of the repository')]
final class FixtureDirectoriesTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function directories(): array
    {
        $directories = [
            'application' => 'jaco/test/asset/jaco',
            'backups' => 'jaco/test/asset/jaco-data/backups',
            'page templates' => 'jaco/test/asset/jaco-data/templates/page',
            'content templates' => 'jaco/test/asset/jaco-data/templates/content',
            'site templates' => 'jaco/test/asset/jaco-data/templates/site',
            'media' => 'jaco/test/asset/media',
        ];

        $cases = [];
        foreach ($directories as $name => $path) {
            $cases[$name] = [$path];
        }

        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('directories')]
    public function testThePlaceholderIsTracked(string $directory): void
    {
        $root = \dirname(__DIR__, 2);
        $placeholder = $root . '/' . $directory . '/.gitignore';

        $this->assertFileExists(
            $placeholder,
            $directory . ' has no .gitignore, so a fresh clone has no such directory',
        );
    }
}

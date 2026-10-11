<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Every json file below jaco/ must parse: a broken schema or translation file is then
 * caught by the test suite and the pipeline instead of at runtime.
 *
 * Dependencies and generated runtime data are skipped: vendor/ holds the json files of
 * the libraries, runtime/ the login history. composer.lock does not match "*.json" and
 * is checked by composer itself.
 */
#[TestDox('Json files')]
final class JsonFilesTest extends TestCase
{
    public function testEveryJsonFileIsValid(): void
    {
        $files = $this->jsonFiles();
        $this->assertNotSame([], $files, 'no json file was found');

        foreach ($files as $file) {
            json_decode((string) file_get_contents(dirname(__DIR__) . '/' . $file));

            $this->assertSame(JSON_ERROR_NONE, json_last_error(), $file . ': ' . json_last_error_msg());
        }
    }

    /**
     * Paths relative to jaco/, so the failure message stays readable in the pipeline.
     *
     * @return list<string>
     */
    private function jsonFiles(): array
    {
        $appDir = dirname(__DIR__);
        $directory = new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS);
        $filtered = new \RecursiveCallbackFilterIterator(
            $directory,
            static function (\SplFileInfo $current): bool {
                if ($current->isDir()) {
                    return !in_array($current->getFilename(), ['vendor', 'runtime'], true);
                }

                return str_ends_with($current->getFilename(), '.json');
            },
        );

        $files = [];
        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($filtered) as $file) {
            $files[] = substr($file->getPathname(), strlen($appDir) + 1);
        }
        sort($files);

        return $files;
    }
}

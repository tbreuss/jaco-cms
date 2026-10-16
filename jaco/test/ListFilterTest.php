<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\list;

#[TestDox('List filtering')]
final class ListFilterTest extends TestCase
{
    public function testEmptyFilterReturnsEverything(): void
    {
        $items = [(object) ['file' => 'a.jpg'], (object) ['file' => 'b.jpg']];

        $this->assertSame($items, array_values(list\filter_list($items, '')));
    }

    public function testDirectoryPathFindsItsFiles(): void
    {
        $items = [
            (object) ['file' => 'bilder/2026/foto.jpg'],
            (object) ['file' => 'upload/logo.png'],
        ];

        $found = array_values(list\filter_list($items, 'bilder/2026'));

        $this->assertCount(1, $found);
        $this->assertSame('bilder/2026/foto.jpg', $found[0]->file);
    }

    public function testPipeSpellingStillFindsBackups(): void
    {
        $items = [
            (object) ['file' => '20260104_065852_2uoc_index.html'],
            (object) ['file' => '20260104_065852_2uoc_sub|page.html'],
        ];

        $found = array_values(list\filter_list($items, 'sub/page'));

        $this->assertCount(1, $found);
        $this->assertSame('20260104_065852_2uoc_sub|page.html', $found[0]->file);
    }

    public function testMatchingIgnoresCase(): void
    {
        $items = [(object) ['file' => 'Upload/Logo.PNG']];

        $this->assertCount(1, list\filter_list($items, 'upload/logo'));
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Page template API (template/pages)')]
final class ApiTemplatePagesTest extends ApiTestCase
{
    private const TEMPLATE = 'api-template-list.html';

    private const CONTENT = "<!DOCTYPE html>\n<html lang=\"en\"><head><title>Template list title</title></head>"
        . "<body></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->config->templatePageDir . '/' . self::TEMPLATE, self::CONTENT);
    }

    protected function tearDown(): void
    {
        @unlink($this->config->templatePageDir . '/' . self::TEMPLATE);

        parent::tearDown();
    }

    public function testTemplatesAreListedWithTheirTitle(): void
    {
        $response = $this->api('template/pages', method: 'GET');

        $this->assertSame(Status::OK, $response->status);

        $items = (array) json_decode((string) $response->body, true);
        $item = $this->itemWithFile($items, self::TEMPLATE);

        $this->assertSame('Template list title', $item['title']);
        $this->assertIsInt($item['created']);
    }

    public function testTemplatesAreSortedByTitle(): void
    {
        // the dropdown shows the title; unsorted it reads as "en before de" for the
        // same template, and "Repeating cards" before "Landing page"
        $names = ['zz-a.html' => 'B title', 'zz-b.html' => 'A title', 'zz-c.html' => 'C title'];
        foreach ($names as $file => $title) {
            file_put_contents(
                $this->config->templatePageDir . '/' . $file,
                "<!DOCTYPE html>\n<html lang=\"en\"><head><title>{$title}</title></head><body></body></html>\n",
            );
        }

        try {
            $items = (array) json_decode((string) $this->api('template/pages', method: 'GET')->body, true);
            $titles = array_values(array_filter(
                array_column($items, 'title'),
                static fn(string $title): bool => \in_array($title, ['A title', 'B title', 'C title'], true),
            ));

            $this->assertSame(['A title', 'B title', 'C title'], $titles, 'sorted by title, not by file name');
        } finally {
            foreach (array_keys($names) as $file) {
                @unlink($this->config->templatePageDir . '/' . $file);
            }
        }
    }

    public function testTheDefaultLanguageComesFirst(): void
    {
        // the site language groups the list: the default language first, the others
        // after it. Sorting by title alone would interleave them.
        $config = $this->config;
        $default = $config->defaultLanguage;
        $other = $default === 'de' ? 'en' : 'de';
        $names = ["zz-$other.html" => 'Aaaa', "zz-$default.html" => 'Zzzz'];

        foreach ($names as $file => $title) {
            file_put_contents(
                $config->templatePageDir . '/' . $file,
                "<!DOCTYPE html>\n<html lang=\"en\"><head><title>{$title}</title></head><body></body></html>\n",
            );
        }

        try {
            $items = (array) json_decode((string) $this->api('template/pages', method: 'GET')->body, true);
            $titles = array_values(array_filter(
                array_column($items, 'title'),
                static fn(string $title): bool => \in_array($title, ['Aaaa', 'Zzzz'], true),
            ));

            // 'Zzzz' is in the default language and comes first although it sorts last
            $this->assertSame(['Zzzz', 'Aaaa'], $titles);
        } finally {
            foreach (array_keys($names) as $file) {
                @unlink($config->templatePageDir . '/' . $file);
            }
        }
    }

    public function testRequestWithoutTokenIsForbidden(): void
    {
        $response = $this->api('template/pages', method: 'GET', authenticated: false);

        $this->assertSame(Status::UNAUTHORIZED, $response->status);
    }
}

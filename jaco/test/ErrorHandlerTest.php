<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\error;

#[TestDox('Error responses hide internals')]
final class ErrorHandlerTest extends TestCase
{
    public function testReferenceIsShortHex(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{12}$/', error\generate_reference());
        $this->assertNotSame(error\generate_reference(), error\generate_reference());
    }

    public function testExceptionResponseContainsNoPaths(): void
    {
        $output = $this->runHandler('new \\Exception("boom-secret")');

        $this->assertStringContainsString('An unexpected error occurred (reference ', $output);
        $this->assertStringNotContainsString('.php', $output);
        $this->assertStringNotContainsString('boom-secret', $output);
    }

    private function runHandler(string $expression): string
    {
        $script = tempnam(sys_get_temp_dir(), 'jaco-err') . '.php';
        file_put_contents(
            $script,
            '<?php declare(strict_types=1);'
            . ' require ' . var_export(__DIR__ . '/../vendor/autoload.php', true) . ';'
            . ' jaco\\core\\error\\exception_handler(' . $expression . ');',
        );

        try {
            $output = shell_exec(PHP_BINARY . ' ' . escapeshellarg($script) . ' 2>/dev/null');
        } finally {
            @unlink($script);
        }

        $this->assertIsString($output);

        return $output;
    }
}

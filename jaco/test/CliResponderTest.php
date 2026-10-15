<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\cli\auth\token;
use jaco\cli\system\log;
use jaco\cli\version;
use jaco\domain\Payload;

#[TestDox('CLI responders return output and exit code instead of exiting')]
final class CliResponderTest extends TestCase
{
    public function testTokenResponderPrintsTheTokenWithExitCodeZero(): void
    {
        [$output, $code] = token\responder(Payload::success(['token' => 'abc']), 'demo');

        $this->assertSame(0, $code);
        $this->assertSame('The token for user "demo" is:' . PHP_EOL . 'abc' . PHP_EOL, $output);
    }

    public function testTokenCommandSucceedsWithRealCredentials(): void
    {
        $config = test_config();

        [$output, $code] = token\command($config, $config->username, $config->password);

        $this->assertSame(0, $code);
        $this->assertStringStartsWith('The token for user "test_user" is:' . PHP_EOL, $output);
    }

    public function testTokenCommandMapsFieldNamesWithExitCodeOne(): void
    {
        $config = test_config();

        [$output, $code] = token\command($config, '', '');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('(user)', $output);
        $this->assertStringContainsString('(pass)', $output);
    }

    public function testTokenCommandWithTotpAsksForTheCode(): void
    {
        $config = test_config_with(['totpSecret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);

        [$output, $code] = token\command($config, $config->username, $config->password);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--code', $output);
    }

    public function testTokenCommandWithTotpCodeSucceeds(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $config = test_config_with(['totpSecret' => $secret]);

        [$output, $code] = token\command(
            $config,
            $config->username,
            $config->password,
            \jaco\core\totp\code_at($secret, time()),
        );

        $this->assertSame(0, $code);
        $this->assertStringStartsWith('The token for user "test_user" is:' . PHP_EOL, $output);
    }

    public function testTokenCommandWithWrongTotpCodeFails(): void
    {
        $config = test_config_with(['totpSecret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);

        [, $code] = token\command($config, $config->username, $config->password, '000000');

        $this->assertSame(1, $code);
    }

    public function testInvalidPayloadRendersMappedErrorsWithExitCodeOne(): void
    {
        [$output, $code] = token\responder(
            Payload::invalid([['username', 'Value cannot be blank.']]),
            'demo',
        );

        $this->assertSame(1, $code);
        $this->assertSame('Validation Error: Value cannot be blank. (user)' . PHP_EOL, $output);
    }

    public function testErrorPayloadRendersServerErrorWithExitCodeTwo(): void
    {
        [$output, $code] = token\responder(Payload::error([['file', 'boom']]), 'demo');

        $this->assertSame(2, $code);
        $this->assertSame('Server Error: boom ' . PHP_EOL, $output);
    }

    public function testUnknownStatusRendersServerErrorWithExitCodeThree(): void
    {
        [$output, $code] = token\responder(new Payload('BOGUS', []), 'demo');

        $this->assertSame(3, $code);
        $this->assertSame('Server Error: Invalid service status ' . PHP_EOL, $output);
    }

    public function testLogResponderPrintsEveryLineWithExitCodeZero(): void
    {
        [$output, $code] = log\responder(Payload::success(['first', 'second']));

        $this->assertSame(0, $code);
        $this->assertSame('first' . PHP_EOL . 'second' . PHP_EOL, $output);
    }

    public function testVersionCommandPrintsNameAndVersionWithExitCodeZero(): void
    {
        [$output, $code] = version\command(test_config());

        $this->assertSame(0, $code);
        $this->assertSame('jaco 0.1.0' . PHP_EOL, $output);
    }
}

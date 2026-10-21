<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function jaco\cli\auth\password\command;
use function jaco\cli\auth\password\generate_password;

/**
 * The command exists so an operator can paste a digest into settings.php
 * without putting a plain-text password into shell history.
 */
#[TestDox('CLI password command')]
final class CliPasswordTest extends TestCase
{
    public function testCommandPrintsThePasswordAndAMatchingHash(): void
    {
        [$output, $code] = command();

        $this->assertSame(0, $code);
        $this->assertSame(1, preg_match('/^Generated password: (\S+)$/m', $output, $password));
        $this->assertSame(1, preg_match('/^Password hash: (\S+)$/m', $output, $hash));

        $plain = $password[1] ?? '';
        $digest = $hash[1] ?? '';

        $this->assertTrue(password_verify($plain, $digest));
        $this->assertSame(24, strlen($plain));
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Za-km-z2-9]+$/', $plain, 'ambiguous characters are excluded');
    }

    public function testChosenPasswordIsHashedWithoutPrintingIt(): void
    {
        [$output, $code] = command('my chosen password');

        $this->assertSame(0, $code);
        $this->assertStringNotContainsString('my chosen password', $output);
        $this->assertStringNotContainsString('Generated password:', $output);
        $this->assertSame(1, preg_match('/^Password hash: (\S+)$/m', $output, $hash));
        $this->assertTrue(password_verify('my chosen password', $hash[1] ?? ''));
    }

    public function testEmptyPasswordIsRejected(): void
    {
        [$output, $code] = command('');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('must not be empty', $output);
    }

    public function testEveryRunGeneratesADifferentPassword(): void
    {
        $this->assertNotSame(generate_password(), generate_password());
    }

    public function testTheMinimumLengthIsEnforced(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        generate_password(11);
    }
}

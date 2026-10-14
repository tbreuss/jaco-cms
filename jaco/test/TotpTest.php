<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\totp;

#[TestDox('TOTP codes (RFC 6238 vectors)')]
final class TotpTest extends TestCase
{
    // RFC 6238 appendix B, SHA1, secret "12345678901234567890".
    // The vectors are 8 digits, the app uses the last 6 of them.
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public static function rfcVectors(): array
    {
        return [
            'counter 1' => [59, '287082'],
            'counter 37037036' => [1111111109, '081804'],
            'counter 37037037' => [1111111111, '050471'],
            'counter 41152263' => [1234567890, '005924'],
            'counter 66666666' => [2000000000, '279037'],
            'counter 666666666' => [20000000000, '353130'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function testRfcVectors(int $timestamp, string $expected): void
    {
        $this->assertSame($expected, totp\code_at(self::SECRET, $timestamp));
        $this->assertTrue(totp\verify_code(self::SECRET, $expected, $timestamp));
    }

    public function testWrongCodeIsRejected(): void
    {
        $this->assertFalse(totp\verify_code(self::SECRET, '000000', 59));
    }

    public function testNeighbouringWindowIsAccepted(): void
    {
        $code = totp\code_at(self::SECRET, 59);
        $this->assertTrue(totp\verify_code(self::SECRET, $code, 59 + 30));
        $this->assertTrue(totp\verify_code(self::SECRET, $code, 59 - 30));
    }

    public function testDistantWindowIsRejected(): void
    {
        $code = totp\code_at(self::SECRET, 59);
        $this->assertFalse(totp\verify_code(self::SECRET, $code, 59 + 60));
    }

    public function testEmptySecretOrCodeIsRejected(): void
    {
        $this->assertFalse(totp\verify_code('', '94287082', 59));
        $this->assertFalse(totp\verify_code(self::SECRET, '', 59));
    }

    public function testSecretRoundtrip(): void
    {
        $secret = totp\generate_secret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertTrue(totp\verify_code($secret, totp\code_at($secret, time())));
    }

    public function testProvisioningUri(): void
    {
        $uri = totp\provisioning_uri(self::SECRET, 'admin');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=' . self::SECRET, $uri);
    }

    public function testQrSvgContainsSvgMarkup(): void
    {
        $svg = totp\qr_svg(totp\provisioning_uri(self::SECRET, 'admin'));

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    public function testQrTextHasMultipleLines(): void
    {
        $text = totp\qr_text(totp\provisioning_uri(self::SECRET, 'admin'));
        $lines = explode("\n", trim($text));

        $this->assertGreaterThan(10, count($lines));
        $this->assertCount(1, array_unique(array_map('strlen', $lines)), 'all rows share one width');
    }
}

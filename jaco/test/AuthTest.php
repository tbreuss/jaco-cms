<?php declare(strict_types=1);

namespace jaco\tests;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;

use const jaco\PASSWORD;
use const jaco\SECRET_KEY;
use const jaco\USERNAME;

/**
 * @testdox Authentication tokens
 */
final class AuthTest extends TestCase
{
    public function testValidCredentialsAreAccepted(): void
    {
        $token = auth\authenticate(USERNAME, PASSWORD);

        $this->assertIsString($token);
        $this->assertTrue(auth\is_authenticated($token));
    }

    public function testWrongCredentialsAreRejected(): void
    {
        $this->assertNull(auth\authenticate(USERNAME, PASSWORD . 'wrong'));
        $this->assertNull(auth\authenticate(USERNAME . 'wrong', PASSWORD));
        $this->assertNull(auth\authenticate('', ''));
    }

    public function testTokenIsSignedWithTheConfiguredAlgorithm(): void
    {
        $token = (string) auth\authenticate(USERNAME, PASSWORD);
        $parts = explode('.', $token);
        $header = json_decode((string) base64_decode(strtr($parts[0], '-_', '+/'), true), true);

        $this->assertSame(auth\JWT_ALGORITHM, $header['alg']);
    }

    public function testTokenIsValidForTheConfiguredDuration(): void
    {
        $token = (string) auth\authenticate(USERNAME, PASSWORD);
        $claims = $this->decode($token);

        $this->assertSame(auth\TOKEN_TTL, $claims['exp'] - $claims['iat']);
    }

    public function testTokenWithModifiedPayloadIsRejected(): void
    {
        [$header, $payload, $signature] = explode('.', $this->validToken());

        $claims = (array) json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);
        $claims['user'] = 'someone-else';
        $modifiedPayload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        // the signature still belongs to the original payload
        $this->assertFalse(auth\is_authenticated($header . '.' . $modifiedPayload . '.' . $signature));
    }

    public function testTokenWithTamperedSignatureIsRejected(): void
    {
        [$header, $payload, $signature] = explode('.', $this->validToken());

        // the first character carries data bits, unlike the last one which is padding
        $tamperedSignature = ($signature[0] === 'A' ? 'B' : 'A') . substr($signature, 1);

        $this->assertFalse(auth\is_authenticated($header . '.' . $payload . '.' . $tamperedSignature));
    }

    public function testTokenSignedWithAnotherSecretIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(secret: 'another-secret')));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(claims: [
            'exp' => time() - 10,
        ])));
    }

    public function testTokenThatIsNotValidYetIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(claims: [
            'nbf' => time() + 3600,
            'exp' => time() + 7200,
        ])));
    }

    public function testTokenWithWrongIssuerIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(claims: [
            'iss' => 'ch.example.other',
        ])));
    }

    public function testTokenWithWrongAudienceIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(claims: [
            'aud' => 'ch.example.other',
        ])));
    }

    public function testTokenOfAnotherUserIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(claims: [
            'user' => 'someone-else',
        ])));
    }

    public function testTokenWithoutAdminClaimIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWith(claims: [
            'admin' => false,
        ])));
    }

    public function testTokenWithoutAdminClaimAtAllIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated($this->tokenWithoutAdminClaim()));
    }

    public function testGarbageIsRejected(): void
    {
        $this->assertFalse(auth\is_authenticated(''));
        $this->assertFalse(auth\is_authenticated('not-a-token'));
        $this->assertFalse(auth\is_authenticated('not.a.token'));
    }

    private function validToken(): string
    {
        return (string) auth\authenticate(USERNAME, PASSWORD);
    }

    /** @return array<string, mixed> */
    private function decode(string $token): array
    {
        $parts = explode('.', $token);

        return (array) json_decode((string) base64_decode(strtr($parts[1], '-_', '+/'), true), true);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function tokenWith(array $claims = [], string $secret = SECRET_KEY): string
    {
        $now = time();

        $payload = array_merge([
            'iss' => auth\ISSUER,
            'aud' => auth\AUDIENCE,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'user' => USERNAME,
            'admin' => true,
        ], $claims);

        return JWT::encode($payload, $secret, auth\JWT_ALGORITHM);
    }

    private function tokenWithoutAdminClaim(): string
    {
        $now = time();

        return JWT::encode([
            'iss' => auth\ISSUER,
            'aud' => auth\AUDIENCE,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'user' => USERNAME,
        ], SECRET_KEY, auth\JWT_ALGORITHM);
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;

#[TestDox('Authentication tokens')]
final class AuthTest extends TestCase
{
    public function testValidCredentialsAreAccepted(): void
    {
        $config = test_config();
        $token = auth\authenticate($config, TEST_USERNAME, TEST_PASSWORD);

        $this->assertIsString($token);
        $this->assertTrue(auth\is_authenticated($config, $token));
    }

    public function testWrongCredentialsAreRejected(): void
    {
        $config = test_config();
        $this->assertNull(auth\authenticate($config, TEST_USERNAME, TEST_PASSWORD . 'wrong'));
        $this->assertNull(auth\authenticate($config, TEST_USERNAME . 'wrong', TEST_PASSWORD));
        $this->assertNull(auth\authenticate($config, '', ''));
    }

    public function testPlaintextPasswordInSettingsIsRejected(): void
    {
        // the password is stored hashed; a settings.php that still holds the
        // plaintext must not authenticate anyone, otherwise the hash could be
        // swapped back out unnoticed
        $config = test_config_with(['users' => [TEST_USERNAME => ['password' => TEST_PASSWORD, 'role' => 'admin']]]);

        $this->assertNull(auth\authenticate($config, TEST_USERNAME, TEST_PASSWORD));
    }

    public function testTokenIsSignedWithTheConfiguredAlgorithm(): void
    {
        $config = test_config();
        $token = (string) auth\authenticate($config, TEST_USERNAME, TEST_PASSWORD);
        $parts = explode('.', $token);
        $header = json_decode((string) base64_decode(strtr($parts[0], '-_', '+/'), true), true);

        $this->assertSame(auth\TOKEN_ALGORITHM, $header['alg']);
    }

    public function testTokenIsValidForTheConfiguredDuration(): void
    {
        $config = test_config();
        $token = (string) auth\authenticate($config, TEST_USERNAME, TEST_PASSWORD);
        $claims = $this->decode($token);

        $this->assertSame(auth\TOKEN_TTL, $claims['exp'] - $claims['iat']);
    }

    public function testTokenWithModifiedPayloadIsRejected(): void
    {
        $config = test_config();
        [$header, $payload, $signature] = explode('.', $this->validToken());

        $claims = (array) json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);
        $claims['user'] = 'someone-else';
        $modifiedPayload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        // the signature still belongs to the original payload
        $this->assertFalse(auth\is_authenticated($config, $header . '.' . $modifiedPayload . '.' . $signature));
    }

    public function testTokenWithTamperedSignatureIsRejected(): void
    {
        $config = test_config();
        [$header, $payload, $signature] = explode('.', $this->validToken());

        // the first character carries data bits, unlike the last one which is padding
        $tamperedSignature = ($signature[0] === 'A' ? 'B' : 'A') . substr($signature, 1);

        $this->assertFalse(auth\is_authenticated($config, $header . '.' . $payload . '.' . $tamperedSignature));
    }

    public function testTokenSignedWithAnotherSecretIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, $this->tokenWith(secret: 'another-secret')));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, $this->tokenWith(claims: [
            'exp' => time() - 10,
        ])));
    }

    public function testTokenThatIsNotValidYetIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, $this->tokenWith(claims: [
            'nbf' => time() + 3600,
            'exp' => time() + 7200,
        ])));
    }

    public function testTokenWithWrongIssuerIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, $this->tokenWith(claims: [
            'iss' => 'ch.example.other',
        ])));
    }

    public function testTokenWithWrongAudienceIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, $this->tokenWith(claims: [
            'aud' => 'ch.example.other',
        ])));
    }

    public function testTokenOfAnotherUserIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, $this->tokenWith(claims: [
            'user' => 'someone-else',
        ])));
    }

    public function testTokenWithoutRoleFallsBackToViewer(): void
    {
        $config = test_config();

        // a token issued before roles existed (or one that still carries the old admin
        // flag) keeps working, but only with the least privilege
        $claims = auth\read_token($config, $this->tokenWithoutAdminClaim());

        $this->assertNotNull($claims);
        $this->assertSame(auth\ROLE_VIEWER, $claims['role']);
        $this->assertFalse(auth\role_allows($claims['role'], auth\ROLE_EDITOR));
    }

    public function testAnUnknownRoleFallsBackToViewer(): void
    {
        $config = test_config();

        $claims = auth\read_token($config, $this->tokenWith(claims: ['role' => 'boss']));

        $this->assertNotNull($claims);
        $this->assertSame(auth\ROLE_VIEWER, $claims['role']);
    }

    public function testGarbageIsRejected(): void
    {
        $config = test_config();
        $this->assertFalse(auth\is_authenticated($config, ''));
        $this->assertFalse(auth\is_authenticated($config, 'not-a-token'));
        $this->assertFalse(auth\is_authenticated($config, 'not.a.token'));
    }

    private function validToken(): string
    {
        $config = test_config();
        return (string) auth\authenticate($config, TEST_USERNAME, TEST_PASSWORD);
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
    private function tokenWith(array $claims = [], ?string $secret = null): string
    {
        $config = test_config();
        $secret ??= $config->secretKey;
        $now = time();

        $payload = array_merge([
            'iss' => auth\TOKEN_ISSUER,
            'aud' => auth\TOKEN_AUDIENCE,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'user' => TEST_USERNAME,
            'admin' => true,
        ], $claims);

        return JWT::encode($payload, $secret, auth\TOKEN_ALGORITHM);
    }

    private function tokenWithoutAdminClaim(): string
    {
        $config = test_config();
        $now = time();

        return JWT::encode([
            'iss' => auth\TOKEN_ISSUER,
            'aud' => auth\TOKEN_AUDIENCE,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'user' => TEST_USERNAME,
        ], $config->secretKey, auth\TOKEN_ALGORITHM);
    }
}

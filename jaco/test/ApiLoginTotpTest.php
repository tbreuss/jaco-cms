<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\auth;
use jaco\core\http\Status;
use jaco\core\totp;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Login with two-factor authentication (login)')]
final class ApiLoginTotpTest extends ApiTestCase
{
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        parent::setUp();

        auth\clear_login_history($this->config);
    }

    protected function tearDown(): void
    {
        auth\clear_login_history($this->config);

        parent::tearDown();
    }

    private function configWithTotp(): \jaco\core\config\Config
    {
        return test_config_with(['totpSecret' => self::SECRET]);
    }

    private function credentials(array $extra = []): array
    {
        return array_merge([
            'username' => TEST_USERNAME,
            'password' => TEST_PASSWORD,
        ], $extra);
    }

    public function testPasswordWithoutCodeAsksForTheCode(): void
    {
        $response = $this->api('login', $this->credentials(), 'POST', true, [], $this->configWithTotp());

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertTrue($body['totpRequired']);
    }

    public function testWrongCodeIsRejected(): void
    {
        $response = $this->api(
            'login',
            $this->credentials(['totpCode' => '000000']),
            'POST',
            true,
            [],
            $this->configWithTotp(),
        );

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('totpCode', $body[0]['source']['pointer']);
        $this->assertSame('Invalid authentication code.', $body[0]['detail']);
    }

    public function testCorrectCodeReturnsAToken(): void
    {
        $code = totp\code_at(self::SECRET, time());

        $response = $this->api(
            'login',
            $this->credentials(['totpCode' => $code]),
            'POST',
            true,
            [],
            $this->configWithTotp(),
        );

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }

    public function testPasswordWithoutSecretKeepsTheOldBehavior(): void
    {
        $response = $this->api('login', $this->credentials());

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }
}

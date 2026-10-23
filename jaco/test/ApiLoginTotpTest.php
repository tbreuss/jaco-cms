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
    private const OTHER_USERNAME = 'other_user';
    private const OTHER_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

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
        return test_config_with(['users' => [TEST_USERNAME => ['password' => test_config_password_hash(), 'role' => 'admin', 'totpSecret' => self::SECRET]]]);
    }

    private function configWithTwoUsers(bool $secondHasSecret = true): \jaco\core\config\Config
    {
        $second = ['password' => test_config_password_hash(), 'role' => 'viewer'];

        if ($secondHasSecret) {
            $second['totpSecret'] = self::OTHER_SECRET;
        }

        return test_config_with(['users' => [
            TEST_USERNAME => ['password' => test_config_password_hash(), 'role' => 'admin', 'totpSecret' => self::SECRET],
            self::OTHER_USERNAME => $second,
        ]]);
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
        $response = $this->api('login', $this->credentials(), 'POST', false, [], $this->configWithTotp());

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
            false,
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
            false,
            [],
            $this->configWithTotp(),
        );

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }

    public function testACodeFromOneUserDoesNotUnlockAnother(): void
    {
        $config = $this->configWithTwoUsers();
        $now = time();
        $codeA = totp\code_at(self::SECRET, $now);
        $codeB = totp\code_at(self::OTHER_SECRET, $now);

        // two different secrets must not produce the same six digits in this second
        $this->assertNotSame($codeA, $codeB);

        $response = $this->api(
            'login',
            ['username' => self::OTHER_USERNAME, 'password' => TEST_PASSWORD, 'totpCode' => $codeA],
            'POST',
            false,
            [],
            $config,
        );

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertSame('Invalid authentication code.', $body[0]['detail']);

        // the other user's own code still works: the factor is bound to the account
        $response = $this->api(
            'login',
            ['username' => self::OTHER_USERNAME, 'password' => TEST_PASSWORD, 'totpCode' => $codeB],
            'POST',
            false,
            [],
            $config,
        );

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }

    public function testAUserWithoutASecretDoesNotNeedACode(): void
    {
        // the second factor belongs to the user, not to the installation: one account
        // having a secret must not force the other account to use one
        $response = $this->api(
            'login',
            ['username' => self::OTHER_USERNAME, 'password' => TEST_PASSWORD],
            'POST',
            false,
            [],
            $this->configWithTwoUsers(false),
        );

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }

    public function testPasswordWithoutSecretKeepsTheOldBehavior(): void
    {
        $response = $this->api('login', $this->credentials(), 'POST', false);

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use jaco\core\auth;
use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\core\totp;

/**
 * The throttle through the real route: the domain unit tests cover the logic, these
 * cover the wiring – that the api hands the client address over at all. Without it the
 * fourth attempt would still say "Authentication failed." instead of blocking.
 */
#[TestDox('Login throttle over the api (login)')]
final class ApiLoginThrottleTest extends ApiTestCase
{
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
    private const ATTACKER = '192.0.2.9';
    private const OWNER = '203.0.113.7';

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

    /** @param array<string, string> $extra */
    private function login(array $extra, string $address, ?Config $config = null): Response
    {
        return $this->api(
            'login',
            array_merge(['username' => $this->config->username, 'password' => 'definitely-wrong'], $extra),
            'POST',
            true,
            [],
            $config,
            ['REMOTE_ADDR' => $address],
        );
    }

    private function detail(Response $response): string
    {
        $body = json_decode((string) $response->body, true);

        return (string) ($body[0]['detail'] ?? '');
    }

    public function testTheFourthAttemptFromOneAddressIsBlocked(): void
    {
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            $response = $this->login([], self::ATTACKER);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertSame('Authentication failed.', $this->detail($response));
        }

        $this->assertSame(
            'Too many attempts. Try again in 10 minute(s).',
            $this->detail($this->login([], self::ATTACKER)),
        );
    }

    public function testTheOwnerCanStillLogInWhileTheAttackerIsBlocked(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login([], self::ATTACKER);
        }

        $response = $this->api(
            'login',
            ['username' => $this->config->username, 'password' => TEST_PASSWORD],
            'POST',
            true,
            [],
            null,
            ['REMOTE_ADDR' => self::OWNER],
        );

        $this->assertSame(Status::OK, $response->status);

        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
    }

    public function testWithoutAnAddressTheThrottleStaysOut(): void
    {
        // a request without REMOTE_ADDR (a command line caller) is never blocked
        for ($i = 0; $i < 5; $i++) {
            $response = $this->api('login', ['username' => 'nobody', 'password' => 'wrong']);

            $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);
            $this->assertSame('Authentication failed.', $this->detail($response));
        }
    }

    public function testWrongTwoFactorCodesBlockThatAddressToo(): void
    {
        $config = test_config_with(['totpSecret' => self::SECRET]);
        auth\clear_login_history($config);

        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            // the password has to be right, otherwise the code is never looked at
            $response = $this->login(
                ['password' => TEST_PASSWORD, 'totpCode' => '000000'],
                self::ATTACKER,
                $config,
            );

            $this->assertSame('Invalid authentication code.', $this->detail($response));
        }

        // even the right password and code are rejected from that address now
        $response = $this->login([
            'password' => TEST_PASSWORD,
            'totpCode' => totp\code_at(self::SECRET, time()),
        ], self::ATTACKER, $config);

        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $this->detail($response));
    }
}

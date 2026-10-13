<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\domain\system;

#[TestDox('Login throttle')]
final class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        auth\clear_login_history(test_config());
    }

    protected function tearDown(): void
    {
        auth\clear_login_history(test_config());
    }

    public function testThreeFailuresBlockTheLogin(): void
    {
        $config = test_config();
        $this->assertSame(0, auth\get_login_block_remaining($config));

        auth\record_login_failure($config);
        auth\record_login_failure($config);
        $this->assertSame(0, auth\get_login_block_remaining($config));

        auth\record_login_failure($config);
        $this->assertGreaterThan(0, auth\get_login_block_remaining($config));
    }

    public function testSuccessResetsTheFailures(): void
    {
        $config = test_config();
        auth\record_login_failure($config);
        auth\record_login_failure($config);
        auth\record_login_success($config);

        auth\record_login_failure($config);
        auth\record_login_failure($config);
        $this->assertSame(0, auth\get_login_block_remaining($config));
    }

    public function testFifteenFailuresBlockTheLoginPermanently(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_TOTAL; $i++) {
            auth\record_login_failure($config);
        }

        $this->assertSame(PHP_INT_MAX, auth\get_login_block_remaining($config));
    }

    public function testRotatingUsernamesDoesNotAvoidTheBlock(): void
    {
        $config = test_config();
        system\authenticate_user($config, 'user-one', 'wrong-password');
        system\authenticate_user($config, 'user-two', 'wrong-password');
        system\authenticate_user($config, 'user-three', 'wrong-password');

        // the global bucket is exhausted, no matter the name
        $payload = system\authenticate_user($config, 'user-four', 'wrong-password');

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $payload->data[0][1]);
    }

    public function testBlockedLoginRejectsCorrectCredentials(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            system\authenticate_user($config, $config->username, 'wrong-password');
        }

        // strict: the block holds even with correct credentials
        $payload = system\authenticate_user($config, $config->username, $config->password);

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $payload->data[0][1]);
        $this->assertGreaterThan(0, auth\get_login_block_remaining($config));
    }

    public function testPermanentlyBlockedLoginShowsContactMessage(): void
    {
        $config = test_config();
        // blocked attempts count as well, so the total is reachable
        for ($i = 0; $i < auth\LOGIN_MAX_TOTAL; $i++) {
            system\authenticate_user($config, $config->username, 'wrong-password');
        }

        $payload = system\authenticate_user($config, $config->username, 'wrong-password');

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Login blocked. Contact the administrator.', $payload->data[0][1]);
    }

    public function testHistoryFileStaysSmallUnderBruteForce(): void
    {
        $config = test_config();
        for ($i = 0; $i < 1000; $i++) {
            auth\record_login_failure($config);
        }

        $history = auth\read_login_history($config);

        $this->assertLessThanOrEqual(auth\LOGIN_MAX_ATTEMPTS, count($history['fails']));
        $this->assertSame(1000, $history['total']);
        $this->assertSame(PHP_INT_MAX, auth\get_login_block_remaining($config));
    }

    public function testPermanentBlockRejectsCorrectCredentials(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_TOTAL; $i++) {
            system\authenticate_user($config, $config->username, 'wrong-password');
        }

        $payload = system\authenticate_user($config, $config->username, $config->password);

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Login blocked. Contact the administrator.', $payload->data[0][1]);
        $this->assertSame(PHP_INT_MAX, auth\get_login_block_remaining($config));
    }
}

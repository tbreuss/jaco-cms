<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\auth;
use jaco\domain\system;

use const jaco\PASSWORD;
use const jaco\USERNAME;

/**
 * @testdox Login throttle
 */
final class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        auth\clear_login_history();
    }

    protected function tearDown(): void
    {
        auth\clear_login_history();
    }

    public function testThreeFailuresBlockTheLogin(): void
    {
        $this->assertSame(0, auth\login_blocked());

        auth\record_login_failure();
        auth\record_login_failure();
        $this->assertSame(0, auth\login_blocked());

        auth\record_login_failure();
        $this->assertGreaterThan(0, auth\login_blocked());
    }

    public function testSuccessResetsTheFailures(): void
    {
        auth\record_login_failure();
        auth\record_login_failure();
        auth\record_login_success();

        auth\record_login_failure();
        auth\record_login_failure();
        $this->assertSame(0, auth\login_blocked());
    }

    public function testFifteenFailuresBlockTheLoginPermanently(): void
    {
        for ($i = 0; $i < auth\LOGIN_MAX_TOTAL; $i++) {
            auth\record_login_failure();
        }

        $this->assertSame(PHP_INT_MAX, auth\login_blocked());
    }

    public function testRotatingUsernamesDoesNotAvoidTheBlock(): void
    {
        system\authenticate_user('user-one', 'wrong-password');
        system\authenticate_user('user-two', 'wrong-password');
        system\authenticate_user('user-three', 'wrong-password');

        // the global bucket is exhausted, no matter the name
        $payload = system\authenticate_user('user-four', 'wrong-password');

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $payload->data[0][1]);
    }

    public function testBlockedLoginRejectsCorrectCredentials(): void
    {
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            system\authenticate_user(USERNAME, 'wrong-password');
        }

        // strict: the block holds even with correct credentials
        $payload = system\authenticate_user(USERNAME, PASSWORD);

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $payload->data[0][1]);
        $this->assertGreaterThan(0, auth\login_blocked());
    }

    public function testPermanentlyBlockedLoginShowsContactMessage(): void
    {
        // blocked attempts count as well, so the total is reachable
        for ($i = 0; $i < auth\LOGIN_MAX_TOTAL; $i++) {
            system\authenticate_user(USERNAME, 'wrong-password');
        }

        $payload = system\authenticate_user(USERNAME, 'wrong-password');

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Login blocked. Contact the administrator.', $payload->data[0][1]);
    }

    public function testHistoryFileStaysSmallUnderBruteForce(): void
    {
        for ($i = 0; $i < 1000; $i++) {
            auth\record_login_failure();
        }

        $history = auth\read_login_history();

        $this->assertLessThanOrEqual(auth\LOGIN_MAX_ATTEMPTS, count($history['fails']));
        $this->assertSame(1000, $history['total']);
        $this->assertSame(PHP_INT_MAX, auth\login_blocked());
    }

    public function testPermanentBlockRejectsCorrectCredentials(): void
    {
        for ($i = 0; $i < auth\LOGIN_MAX_TOTAL; $i++) {
            system\authenticate_user(USERNAME, 'wrong-password');
        }

        $payload = system\authenticate_user(USERNAME, PASSWORD);

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Login blocked. Contact the administrator.', $payload->data[0][1]);
        $this->assertSame(PHP_INT_MAX, auth\login_blocked());
    }
}

<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use jaco\cli\auth\unblock;
use jaco\core\auth;
use jaco\domain\system;

#[TestDox('Login throttle')]
final class LoginThrottleTest extends TestCase
{
    /** addresses for tests, RFC 5737 documentation range */
    private const OWNER = '203.0.113.7';
    private const OTHER = '198.51.100.23';
    private const ATTACKER = '192.0.2.9';

    protected function setUp(): void
    {
        auth\clear_login_history(test_config());
    }

    protected function tearDown(): void
    {
        auth\clear_login_history(test_config());
    }

    public function testThreeFailuresFromOneAddressBlockThatAddress(): void
    {
        $config = test_config();
        $this->assertSame(0, auth\get_login_block_remaining($config, self::ATTACKER));

        auth\record_login_failure($config, self::ATTACKER);
        auth\record_login_failure($config, self::ATTACKER);
        $this->assertSame(0, auth\get_login_block_remaining($config, self::ATTACKER));

        auth\record_login_failure($config, self::ATTACKER);
        $this->assertGreaterThan(0, auth\get_login_block_remaining($config, self::ATTACKER));
    }

    public function testAnotherAddressIsNotBlocked(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            auth\record_login_failure($config, self::ATTACKER);
        }

        $this->assertGreaterThan(0, auth\get_login_block_remaining($config, self::ATTACKER));
        $this->assertSame(0, auth\get_login_block_remaining($config, self::OWNER));
    }

    public function testTheOwnerCanStillLogInWhileAnAddressIsBlocked(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            system\authenticate_user($config, TEST_USERNAME, 'wrong-password', address: self::ATTACKER);
        }

        // the attack blocks the attacker, not the owner – that is the whole point
        $payload = system\authenticate_user(
            $config,
            TEST_USERNAME,
            TEST_PASSWORD,
            address: self::OWNER,
        );

        $this->assertTrue($payload->isSuccess);
        $this->assertArrayHasKey('token', $payload->data);
    }

    public function testFailuresFromManyAddressesDoNotBlockTheOwner(): void
    {
        $config = test_config();

        for ($host = 1; $host <= 50; $host++) {
            $address = '192.0.2.' . $host;
            for ($i = 0; $i < 5; $i++) {
                system\authenticate_user($config, TEST_USERNAME, 'wrong-password', address: $address);
            }
        }

        $this->assertGreaterThan(0, auth\get_login_block_remaining($config, '192.0.2.25'));

        $payload = system\authenticate_user(
            $config,
            TEST_USERNAME,
            TEST_PASSWORD,
            address: self::OWNER,
        );

        $this->assertTrue($payload->isSuccess);
    }

    public function testSuccessClearsOnlyTheOwnFailures(): void
    {
        $config = test_config();
        auth\record_login_failure($config, self::OWNER);
        auth\record_login_failure($config, self::OWNER);
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            auth\record_login_failure($config, self::ATTACKER);
        }

        auth\record_login_success($config, self::OWNER);

        auth\record_login_failure($config, self::OWNER);
        auth\record_login_failure($config, self::OWNER);
        $this->assertSame(0, auth\get_login_block_remaining($config, self::OWNER), 'own failures are cleared');
        $this->assertGreaterThan(0, auth\get_login_block_remaining($config, self::ATTACKER), 'others keep theirs');
    }

    public function testRotatingUsernamesDoesNotAvoidTheBlock(): void
    {
        $config = test_config();
        system\authenticate_user($config, 'user-one', 'wrong-password', address: self::ATTACKER);
        system\authenticate_user($config, 'user-two', 'wrong-password', address: self::ATTACKER);
        system\authenticate_user($config, 'user-three', 'wrong-password', address: self::ATTACKER);

        // the bucket is the address, no matter the name
        $payload = system\authenticate_user($config, 'user-four', 'wrong-password', address: self::ATTACKER);

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $payload->data[0][1]);
    }

    public function testBlockedAddressIsRejectedEvenWithCorrectCredentials(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            system\authenticate_user($config, TEST_USERNAME, 'wrong-password', address: self::OTHER);
        }

        // strict: the block holds for that address, correct credentials or not
        $payload = system\authenticate_user(
            $config,
            TEST_USERNAME,
            TEST_PASSWORD,
            address: self::OTHER,
        );

        $this->assertFalse($payload->isSuccess);
        $this->assertSame('Too many attempts. Try again in 10 minute(s).', $payload->data[0][1]);
    }

    public function testCommandLineCallerIsNotThrottled(): void
    {
        $config = test_config();

        for ($i = 0; $i < 10; $i++) {
            $payload = system\authenticate_user($config, TEST_USERNAME, 'wrong-password');
            $this->assertSame('Authentication failed.', $payload->data[0][1]);
        }

        $this->assertSame(['addresses' => []], auth\read_login_history($config));
    }

    public function testHistoryKeepsOnlyTheWindowAndCapsTheListPerAddress(): void
    {
        $config = test_config();
        for ($i = 0; $i < 100; $i++) {
            auth\record_login_failure($config, self::ATTACKER);
        }

        $history = auth\read_login_history($config);

        $this->assertLessThanOrEqual(auth\LOGIN_MAX_ATTEMPTS, count($history['addresses'][self::ATTACKER]));
    }

    public function testAWriteDropsWhatIsOutsideTheWindow(): void
    {
        $config = test_config();
        $expired = time() - auth\LOGIN_BLOCK_SECONDS - 1;

        // straight into the file: reading keeps it, the next write prunes it
        file_put_contents(
            $config->loginHistoryFile,
            (string) json_encode(['addresses' => [
                '192.0.2.200' => [$expired],
                self::ATTACKER => [time()],
            ]]),
        );

        auth\record_login_failure($config, self::OWNER);

        $history = auth\read_login_history($config);

        $this->assertArrayNotHasKey('192.0.2.200', $history['addresses'], 'expired entries are dropped');
        $this->assertArrayHasKey(self::ATTACKER, $history['addresses'], 'current entries stay');
        $this->assertArrayHasKey(self::OWNER, $history['addresses']);
    }

    public function testABlockExpiresAfterTheWindow(): void
    {
        $config = test_config();
        $expired = time() - auth\LOGIN_BLOCK_SECONDS - 1;

        file_put_contents(
            $config->loginHistoryFile,
            (string) json_encode(['addresses' => [self::ATTACKER => [$expired, $expired, $expired]]]),
        );

        $this->assertSame(0, auth\get_login_block_remaining($config, self::ATTACKER));
    }

    public function testHistoryCapsTheNumberOfAddresses(): void
    {
        $config = test_config();
        $addresses = [];

        for ($i = 0; $i < auth\LOGIN_MAX_ADDRESSES + 20; $i++) {
            $address = '10.' . intdiv($i, 65536) . '.' . (intdiv($i, 256) % 256) . '.' . ($i % 256);
            $addresses[$address] = [time() - 5];
        }

        // one fresh address has to survive the cap
        $fresh = '203.0.113.200';
        $addresses[$fresh] = [time()];

        auth\write_login_history($config, ['addresses' => $addresses]);

        $history = auth\read_login_history($config);

        $this->assertCount(auth\LOGIN_MAX_ADDRESSES, $history['addresses']);
        $this->assertArrayHasKey($fresh, $history['addresses'], 'the freshest address survives the cap');
    }

    public function testUnblockCommandClearsEveryAddress(): void
    {
        $config = test_config();
        for ($i = 0; $i < auth\LOGIN_MAX_ATTEMPTS; $i++) {
            auth\record_login_failure($config, self::ATTACKER);
            auth\record_login_failure($config, self::OTHER);
        }
        $this->assertGreaterThan(0, auth\get_login_block_remaining($config, self::ATTACKER));

        [$output, $exitCode] = unblock\command($config);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Login blocks cleared', $output);
        $this->assertSame(0, auth\get_login_block_remaining($config, self::ATTACKER));
        $this->assertSame(0, auth\get_login_block_remaining($config, self::OTHER));
        $this->assertSame(['addresses' => []], auth\read_login_history($config));
    }
}

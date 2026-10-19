<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\config\Config;

if (is_readable(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/ApiTestCase.php';

/**
 * The plaintext that test_config() stores as a hash. authenticate() expects a
 * digest, so tests log in with this value, not with $config->password.
 */
const TEST_PASSWORD = 'test_pass';

/**
 * Hashed once per process: password_hash() with bcrypt cost 12 is expensive,
 * and test_config() is built for every test. Cache the digest so the suite
 * does not pay bcrypt on every Config.
 */
function test_config_password_hash(): string
{
    static $hash = null;
    if ($hash === null) {
        $hash = \password_hash(TEST_PASSWORD, PASSWORD_DEFAULT);
    }
    return $hash;
}

function test_config(): Config
{
    $dir = __DIR__ . '/asset/jaco';

    return new Config(
        appVersion: '0.1.0',
        appDir: $dir,
        appDirName: basename($dir),
        backupDir: $dir . '/backup',
        templatePageDir: $dir . '/page',
        templateContentDir: $dir . '/content',
        webDir: dirname($dir),
        uploadDir: dirname($dir) . '/upload',
        uploadExtensions: ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf'],
        uploadUrl: 'upload',
        pageExtensions: ['htm', 'html'],
        defaultLanguage: 'en',
        directoryPermissions: 0755,
        filePermissions: 0644,
        secretKey: 'test_1234',
        username: 'test_user',
        // hashed like a real settings.php: authenticate() expects a digest, and
        // hashing per run keeps the tests free of a hardcoded fixture hash
        password: test_config_password_hash(),
        totpSecret: '',
        phpErrorReporting: E_ALL,
        phpDisplayErrors: 0,
        phpDisplayStartupErrors: 0,
        phpLogErrors: 1,
        phpErrorLog: __DIR__ . '/runtime/log/error.log',
        loginHistoryFile: __DIR__ . '/runtime/log/loginhistory.json',
        maintenanceMode: false,
        backupRetentionDays: 90,
    );
}

/**
 * @param array<string, mixed> $overrides
 */
function test_config_with(array $overrides): Config
{
    $values = get_object_vars(test_config());
    foreach ($overrides as $key => $value) {
        if (!array_key_exists($key, $values)) {
            throw new \InvalidArgumentException("Unknown config key: $key");
        }
        $values[$key] = $value;
    }
    return new Config(...$values);
}

<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\config\Config;

if (is_readable(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/ApiTestCase.php';
require_once __DIR__ . '/HttpServerTestCase.php';

/**
 * The plaintext that test_config() stores as a hash. authenticate() expects a
 * digest, so tests log in with this value, not with $config->password.
 */
const TEST_USERNAME = 'test_user';
const TEST_PASSWORD = 'test_pass';

/**
 * Hashed once per process and with the lowest cost: test_config() is built for every
 * test, and password_verify() pays the digest's cost on every login, so the production
 * cost would add minutes to the suite (the throttle tests alone log in hundreds of
 * times). Only the tests read this digest, the installer keeps the default cost.
 */
function test_config_password_hash(): string
{
    static $hash = null;
    if ($hash === null) {
        $hash = \password_hash(TEST_PASSWORD, PASSWORD_DEFAULT, ['cost' => 4]);
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
        webDir: dirname($dir),
        dataDir: $dir . '/data',
        backupDir: $dir . '/data/backups',
        templatePageDir: $dir . '/data/templates/page',
        templateContentDir: $dir . '/data/templates/content',
        templateSiteDir: $dir . '/data/templates/site',
        mediaDir: dirname($dir) . '/media',
        uploadExtensions: ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf'],
        mediaUrl: 'media',
        pageExtensions: ['htm', 'html'],
        defaultLanguage: 'en',
        directoryPermissions: 0755,
        filePermissions: 0644,
        secretKey: 'test_1234',
        users: [
            TEST_USERNAME => ['password' => test_config_password_hash(), 'role' => 'admin'],
        ],
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

<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\config\Config;

if (is_readable(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/ApiTestCase.php';

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
        defaultPage: 'index.html',
        defaultLanguage: 'en',
        directoryPermissions: 0755,
        filePermissions: 0644,
        secretKey: 'test_1234',
        username: 'test_user',
        password: 'test_pass',
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

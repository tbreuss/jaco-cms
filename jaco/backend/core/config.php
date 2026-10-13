<?php declare(strict_types=1);

namespace jaco\core\config;

final readonly class Config
{
    public function __construct(
        public string $appVersion,
        public string $appDir,
        public string $appDirName,
        public string $backupDir,
        public string $templatePageDir,
        public string $templateContentDir,
        public string $webDir,
        public string $uploadDir,
        /** @var list<string> */
        public array $uploadExtensions,
        public string $uploadUrl,
        /** @var list<string> */
        public array $pageExtensions,
        public string $defaultPage,
        public string $defaultLanguage,
        public int $directoryPermissions,
        public int $filePermissions,
        public string $secretKey,
        public string $username,
        public string $password,
        public int $phpErrorReporting,
        public int $phpDisplayErrors,
        public int $phpDisplayStartupErrors,
        public int $phpLogErrors,
        public string $phpErrorLog,
        public string $loginHistoryFile,
        public bool $maintenanceMode,
        public int $backupRetentionDays,
    ) {}
}

/**
 * @param array<string, mixed> $settings
 */
function create_default_config(string $appDir, array $settings = []): Config
{
    return new Config(
        appVersion: '0.1.0',
        appDir: $appDir,
        appDirName: basename($appDir),
        backupDir: $appDir . '/_backup',
        templatePageDir: $appDir . '/_template/page',
        templateContentDir: $appDir . '/_template/content',
        webDir: dirname($appDir),
        uploadDir: dirname($appDir) . '/upload',
        uploadExtensions: ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf'],
        uploadUrl: 'upload',
        pageExtensions: ['htm', 'html'],
        defaultPage: 'index.html',
        defaultLanguage: (string) ($settings['defaultLanguage'] ?? 'en'),
        directoryPermissions: 0755,
        filePermissions: 0644,
        secretKey: (string) ($settings['secretKey'] ?? openssl_random_pseudo_bytes(32)),
        username: (string) ($settings['username'] ?? 'admin'),
        password: (string) ($settings['password'] ?? openssl_random_pseudo_bytes(32)),
        phpErrorReporting: (int) ($settings['phpErrorReporting'] ?? E_ALL),
        phpDisplayErrors: (int) ($settings['phpDisplayErrors'] ?? 0),
        phpDisplayStartupErrors: (int) ($settings['phpDisplayStartupErrors'] ?? 0),
        phpLogErrors: (int) ($settings['phpLogErrors'] ?? 1),
        phpErrorLog: $appDir . '/runtime/log/error.log',
        loginHistoryFile: $appDir . '/runtime/log/loginhistory.json',
        maintenanceMode: (bool) ($settings['maintenanceMode'] ?? false),
        backupRetentionDays: (int) ($settings['backupRetentionDays'] ?? 90),
    );
}

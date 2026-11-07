<?php declare(strict_types=1);

namespace jaco\core\config;

final readonly class Config
{
    public function __construct(
        public string $appVersion,
        public string $appDir,
        public string $appDirName,
        public string $webDir,
        public string $dataDir,
        public string $backupDir,
        public string $appBackupDir,
        public string $templatePageDir,
        public string $templateContentDir,
        public string $templateSiteDir,
        public string $mediaDir,
        /** @var list<string> */
        public array $uploadExtensions,
        public string $mediaUrl,
        /** @var list<string> */
        public array $pageExtensions,
        public string $defaultLanguage,
        public int $directoryPermissions,
        public int $filePermissions,
        public string $secretKey,
        public int $phpErrorReporting,
        public int $phpDisplayErrors,
        public int $phpDisplayStartupErrors,
        public int $phpLogErrors,
        public string $phpErrorLog,
        public string $loginHistoryFile,
        public bool $maintenanceMode,
        public int $backupRetentionDays,
        /** @var array<string, array{password?: mixed, role?: mixed, language?: mixed, totpSecret?: mixed, readOnly?: mixed}> name → digest, role and per user preferences */
        public array $users = [],
        public bool $secretKeyIsConfigured = false,
        /** The file the settings were loaded from; null means dataDir/settings.php. */
        public ?string $settingsFile = null,
    ) {}

    /**
     * The directory of one kind of template ("page", "content", "site"), or null
     * for a kind Jaco does not know.
     */
    public function templateDir(string $kind): ?string
    {
        return match ($kind) {
            'page' => $this->templatePageDir,
            'content' => $this->templateContentDir,
            'site' => $this->templateSiteDir,
            default => null,
        };
    }
}

/**
 * Writes the installer credentials to settings.php. Only the given
 * keys are stored, everything else falls back to the defaults in
 * create_default_config, exactly like a hand written settings file
 * that sets nothing else. The output uses the project code style on
 * purpose: generated code is still scanned by the cs check.
 *
 * @param array<string, mixed> $settings scalars and the users list
 */
function write_settings(string $dataDir, array $settings): bool
{
    // the same generator the later edits use, so a file written by the installer
    // and one written by a settings change look the same
    $written = file_put_contents($dataDir . '/settings.php', settings_source($settings)) !== false;

    // keep credentials unreadable for other users on shared hosts
    if ($written) {
        @chmod($dataDir . '/settings.php', 0640);
    }

    return $written;
}

/**
 * Sets one top-level key in settings.php.
 *
 * The file is read and written as a whole, like every other edit: the same
 * approach for all of them, and a value that is not a scalar (an array such as
 * uploadExtensions) is written instead of being refused because it does not fit
 * on one line. A key that does not exist yet is added.
 */
function write_setting(string $dataDir, string $key, mixed $value): bool
{
    $file = $dataDir . '/settings.php';

    if (!is_file($file)) {
        return false;
    }

    $settings = read_settings($file);

    if ($settings === null) {
        return false;
    }

    $settings[$key] = $value;

    return write_source($file, settings_source($settings));
}

/**
 * The settings file this config belongs to. It is the one in the data directory,
 * unless the caller pointed the application at another file (the E2E server uses
 * a disposable one); writes must land in the file that was read.
 */
function settings_file(Config $config): string
{
    return $config->settingsFile ?? $config->dataDir . '/settings.php';
}

/**
 * Replaces one field of one user in the users list of settings.php.
 *
 * settings.php is a managed file: it is read with require, changed in memory and
 * written back in one piece. Comments placed there by hand do not survive that,
 * which is the price for not parsing PHP source by hand - the previous version
 * scanned for "'users'" and a bracket pair, and a comment mentioning the word
 * was enough to refuse the write.
 *
 * A null value removes the field again, which is how the second factor is turned
 * off without leaving a misleading "totpSecret => null" in the file.
 */
function write_user_field(string $settingsFile, string $username, string $field, ?string $value): bool
{
    if ($field === '') {
        return false;
    }

    if (!is_file($settingsFile)) {
        return false;
    }

    $settings = read_settings($settingsFile);

    if ($settings === null) {
        return false;
    }

    $users = $settings['users'] ?? null;

    if (!is_array($users) || !isset($users[$username]) || !is_array($users[$username])) {
        return false;
    }

    if ($value === null) {
        unset($users[$username][$field]);
    } else {
        $users[$username][$field] = $value;
    }

    $settings['users'] = $users;

    return write_source($settingsFile, settings_source($settings));
}

/**
 * The settings array of a file, or null when it does not return one. A broken file
 * must never be written back, which would turn a readable failure into data loss.
 *
 * @return array<string, mixed>|null
 */
function read_settings(string $file): ?array
{
    $settings = include $file;

    return is_array($settings) ? $settings : null;
}

/**
 * The settings file as PHP source in the project's own style: short arrays,
 * four-space indentation, strict types. The output of this function replaces the
 * file as a whole, so it has to parse (write_source() checks that) and read like
 * the template it replaces.
 *
 * @param array<string, mixed> $settings
 */
function settings_source(array $settings): string
{
    return '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL
        . settings_header() . PHP_EOL . PHP_EOL
        . 'return ' . export_value($settings, 0) . ';' . PHP_EOL;
}

/**
 * The note at the top of a written settings file. It answers the two questions a
 * reader has after a setting changed behind their back: why their comments are gone,
 * and where the documentation of the keys lives.
 *
 * The template is named as an operator sees it, in the installation: jaco/ next to
 * the data directory. An absolute path would put the server layout into a file that
 * is often shared for support.
 */
function settings_header(): string
{
    return '// Managed by Jaco: this file is rewritten as a whole when a setting changes,'
        . PHP_EOL . '// so comments written here do not survive. Keys, roles, TOTP and the data'
        . PHP_EOL . '// directory are documented in jaco/settings.dist.php.';
}

/**
 * var_export() for arrays, but with the short syntax and real indentation: the
 * built-in writes "array (\n  'admin' => \n  array (" and loses the style of the
 * file it replaces. Scalars are handed to var_export(), which quotes and escapes
 * them correctly.
 */
function export_value(mixed $value, int $level): string
{
    if (!is_array($value)) {
        return var_export($value, true);
    }

    if ($value === []) {
        return '[]';
    }

    $pad = str_repeat('    ', $level + 1);
    $close = str_repeat('    ', $level);
    $isList = array_is_list($value);
    $lines = [];

    foreach ($value as $key => $item) {
        $lines[] = $isList
            ? $pad . export_value($item, $level + 1)
            : $pad . var_export($key, true) . ' => ' . export_value($item, $level + 1);
    }

    return '[' . PHP_EOL . implode(',' . PHP_EOL, $lines) . ',' . PHP_EOL . $close . ']';
}

/**
 * Writes a generated settings source: parsed first, so a broken edit never reaches the
 * disk, then written to a temporary .php file and renamed over the target.
 */
function write_source(string $file, string $content): bool
{
    try {
        // the tokens are unused: the call only has to throw on a syntax error
        $tokens = token_get_all($content, TOKEN_PARSE);
    } catch (\ParseError) {
        return false;
    }
    unset($tokens);

    $mode = fileperms($file);
    $temporary = tempnam(dirname($file), '.settings-');

    if ($temporary === false) {
        return false;
    }

    $temporaryPhp = $temporary . '.php';
    $written = file_put_contents($temporaryPhp, $content);
    unlink($temporary);

    if ($written === false || rename($temporaryPhp, $file) === false) {
        if (is_file($temporaryPhp)) {
            unlink($temporaryPhp);
        }

        return false;
    }

    if ($mode !== false) {
        chmod($file, $mode & 0777);
    }

    return true;
}

/**
 * @param array<string, mixed> $settings
 */
function create_default_config(string $appDir, array $settings = [], ?string $settingsFile = null): Config
{
    // fail closed: empty values are treated like missing ones, so a verbatim
    // copy of settings.dist.php can never log in with empty credentials or
    // sign tokens with the public example key. Random fallbacks are
    // unguessable, logging in stays impossible until real values are set.
    $secretKey = (string) ($settings['secretKey'] ?? '');

    // the upload directory stays inside the web root: the value is checked
    // untrimmed, so neither a leading slash nor a traversal segment can
    // turn into a relative path by accident; both fall back to the default
    // and keep listings, backups and the preview working
    $mediaDirName = (string) ($settings['mediaDir'] ?? '');
    if (!\preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#', $mediaDirName)) {
        $mediaDirName = 'media';
    }

    // Operator data lives next to the code, not inside it: templates and
    // backups must survive replacing the jaco/ directory.
    $dataDirName = (string) ($settings['dataDir'] ?? '');
    if (!\preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#', $dataDirName)) {
        $dataDirName = 'jaco-data';
    }
    $webDir = dirname($appDir);
    $dataDir = $webDir . '/' . $dataDirName;

    return new Config(
        appVersion: '0.1.0',
        appDir: $appDir,
        appDirName: basename($appDir),
        webDir: $webDir,
        dataDir: $dataDir,
        backupDir: $dataDir . '/backups',
        appBackupDir: $dataDir . '/app-backups',
        templatePageDir: $dataDir . '/templates/page',
        templateContentDir: $dataDir . '/templates/content',
        templateSiteDir: $dataDir . '/templates/site',
        mediaDir: $webDir . '/' . $mediaDirName,
        uploadExtensions: ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf'],
        mediaUrl: $mediaDirName,
        pageExtensions: ['htm', 'html'],
        defaultLanguage: (string) ($settings['defaultLanguage'] ?? 'en'),
        directoryPermissions: 0755,
        filePermissions: 0644,
        secretKey: \strlen($secretKey) >= 32 ? $secretKey : (string) openssl_random_pseudo_bytes(32),
        secretKeyIsConfigured: \strlen($secretKey) >= 32,
        phpErrorReporting: (int) ($settings['phpErrorReporting'] ?? E_ALL),
        phpDisplayErrors: (int) ($settings['phpDisplayErrors'] ?? 0),
        phpDisplayStartupErrors: (int) ($settings['phpDisplayStartupErrors'] ?? 0),
        phpLogErrors: (int) ($settings['phpLogErrors'] ?? 1),
        phpErrorLog: $dataDir . '/runtime/log/error.log',
        loginHistoryFile: $dataDir . '/runtime/log/loginhistory.json',
        maintenanceMode: (bool) ($settings['maintenanceMode'] ?? false),
        backupRetentionDays: (int) ($settings['backupRetentionDays'] ?? 90),
        // users in settings.php; empty means nobody can log in
        users: is_array($settings['users'] ?? null) ? $settings['users'] : [],
        // writes must go to the file that was actually read, not necessarily to
        // dataDir (the E2E server points at a disposable settings file)
        settingsFile: $settingsFile,
    );
}

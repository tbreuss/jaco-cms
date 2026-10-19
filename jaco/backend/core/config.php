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
        public string $defaultLanguage,
        public int $directoryPermissions,
        public int $filePermissions,
        public string $secretKey,
        public string $totpSecret,
        public int $phpErrorReporting,
        public int $phpDisplayErrors,
        public int $phpDisplayStartupErrors,
        public int $phpLogErrors,
        public string $phpErrorLog,
        public string $loginHistoryFile,
        public bool $maintenanceMode,
        public int $backupRetentionDays,
        /** @var array<string, array{password?: mixed, role?: mixed}> name → digest and role */
        public array $users = [],
    ) {}
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
function write_settings(string $appDir, array $settings): bool
{
    $content = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL;

    foreach ($settings as $key => $value) {
        // var_export keeps strings quoted and also writes nested arrays (the users list)
        $content .= '    ' . var_export($key, true) . ' => ' . var_export($value, true) . ',' . PHP_EOL;
    }

    $content .= '];' . PHP_EOL;

    return file_put_contents($appDir . '/settings.php', $content) !== false;
}

/**
 * Writes one value into an existing settings.php and leaves the rest of the file alone:
 * comments survive, every other key keeps its value, a missing key is appended before
 * the closing bracket. Only scalar values are supported, which is all settings.php holds.
 *
 * The patched source is parsed before it replaces the file, so a broken edit never
 * reaches the disk. The write goes through a temporary file in the same directory and a
 * rename; the temporary file carries a .php suffix, so a request to it executes it
 * instead of serving the settings as text.
 */
function write_setting(string $appDir, string $key, mixed $value): bool
{
    $file = $appDir . '/settings.php';

    if (!is_file($file)) {
        return false;
    }

    $source = file_get_contents($file);

    if (!is_string($source)) {
        return false;
    }

    $keySource = var_export($key, true);
    $valueSource = var_export($value, true);
    $quotedKey = preg_quote($key, '/');

    $patched = preg_replace_callback(
        '/^([ \t]*[\'"]' . $quotedKey . '[\'"][ \t]*=>[ \t]*).+?(,?[ \t]*)$/m',
        static fn(array $match): string => $match[1] . $valueSource . $match[2],
        $source,
        1,
        $count,
    );

    if ($patched === null) {
        return false;
    }

    if ($count === 0) {
        // present but not on one line (an array, for example): appending would create a
        // second entry that shadows the first, so fail instead
        if (preg_match('/[\'"]' . $quotedKey . '[\'"]\s*=>/', $source) === 1) {
            return false;
        }

        $patched = preg_replace_callback(
            '/^(\];)[ \t]*$/m',
            static fn(array $match): string => '    ' . $keySource . ' => ' . $valueSource . ',' . PHP_EOL . $match[1],
            $source,
            1,
            $count,
        );

        if ($patched === null || $count === 0) {
            return false;
        }
    }

    return write_source($appDir, $file, $patched);
}

/**
 * Replaces one user's password digest in the users list of settings.php. A nested array
 * cannot be patched line by line, so the block is located with a balanced bracket scan,
 * rewritten as a whole and written through the same guard as write_setting().
 */
function write_user_password(string $appDir, string $username, string $digest): bool
{
    $file = $appDir . '/settings.php';

    if (!is_file($file)) {
        return false;
    }

    $source = file_get_contents($file);

    if (!is_string($source)) {
        return false;
    }

    $key = strpos($source, "'users'");

    if ($key === false) {
        return false;
    }

    $open = strpos($source, '[', $key);
    $close = $open === false ? null : matching_bracket($source, $open);

    if ($open === false || $close === null) {
        return false;
    }

    $settings = include $file;
    $users = is_array($settings) && is_array($settings['users'] ?? null) ? $settings['users'] : [];

    if (!isset($users[$username]) || !is_array($users[$username])) {
        return false;
    }

    $users[$username] = array_merge($users[$username], ['password' => $digest]);

    return write_source($appDir, $file, substr($source, 0, $open) . var_export($users, true) . substr($source, $close + 1));
}

/**
 * Position of the bracket closing the one at $open, or null when the source is unbalanced.
 */
function matching_bracket(string $source, int $open): ?int
{
    $depth = 0;
    $length = strlen($source);

    for ($index = $open; $index < $length; $index++) {
        if ($source[$index] === '[') {
            $depth++;
        } elseif ($source[$index] === ']') {
            $depth--;
            if ($depth === 0) {
                return $index;
            }
        }
    }

    return null;
}

/**
 * Writes a generated settings source: parsed first, so a broken edit never reaches the
 * disk, then written to a temporary .php file and renamed over the target.
 */
function write_source(string $appDir, string $file, string $content): bool
{
    try {
        // the tokens are unused: the call only has to throw on a syntax error
        $tokens = token_get_all($content, TOKEN_PARSE);
    } catch (\ParseError) {
        return false;
    }
    unset($tokens);

    $mode = fileperms($file);
    $temporary = tempnam($appDir, '.settings-');

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
function create_default_config(string $appDir, array $settings = []): Config
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
    $uploadDirName = (string) ($settings['uploadDir'] ?? '');
    if (!\preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#', $uploadDirName)) {
        $uploadDirName = 'upload';
    }

    return new Config(
        appVersion: '0.1.0',
        appDir: $appDir,
        appDirName: basename($appDir),
        backupDir: $appDir . '/_backup',
        templatePageDir: $appDir . '/_template/page',
        templateContentDir: $appDir . '/_template/content',
        webDir: dirname($appDir),
        uploadDir: dirname($appDir) . '/' . $uploadDirName,
        uploadExtensions: ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf'],
        uploadUrl: $uploadDirName,
        pageExtensions: ['htm', 'html'],
        defaultLanguage: (string) ($settings['defaultLanguage'] ?? 'en'),
        directoryPermissions: 0755,
        filePermissions: 0644,
        secretKey: \strlen($secretKey) >= 32 ? $secretKey : (string) openssl_random_pseudo_bytes(32),
        totpSecret: (string) ($settings['totpSecret'] ?? ''),
        phpErrorReporting: (int) ($settings['phpErrorReporting'] ?? E_ALL),
        phpDisplayErrors: (int) ($settings['phpDisplayErrors'] ?? 0),
        phpDisplayStartupErrors: (int) ($settings['phpDisplayStartupErrors'] ?? 0),
        phpLogErrors: (int) ($settings['phpLogErrors'] ?? 1),
        phpErrorLog: $appDir . '/runtime/log/error.log',
        loginHistoryFile: $appDir . '/runtime/log/loginhistory.json',
        maintenanceMode: (bool) ($settings['maintenanceMode'] ?? false),
        backupRetentionDays: (int) ($settings['backupRetentionDays'] ?? 90),
        // users in settings.php; empty means "use the single username/password pair",
        // which then counts as the admin (see auth\find_user)
        users: is_array($settings['users'] ?? null) ? $settings['users'] : [],
    );
}

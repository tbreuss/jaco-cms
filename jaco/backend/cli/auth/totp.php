<?php declare(strict_types=1);

namespace jaco\cli\auth\totp;

use jaco\core\auth;
use jaco\core\config\Config;

use function jaco\core\config\write_user_field;
use function jaco\core\totp\generate_secret;
use function jaco\core\totp\provisioning_uri;
use function jaco\core\totp\qr_text;

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function setup_command(Config $config, string $username = ''): array
{
    $account = resolve_user($config, $username);

    if ($account === null) {
        return [user_error($config, $username), 1];
    }

    $secret = generate_secret();

    if (!write_user_field($config->appDir, $account, 'totpSecret', $secret)) {
        return ["Could not write settings.\n", 1];
    }

    $output = sprintf('Two-factor authentication is now required for "%s".', $account) . PHP_EOL;
    $output .= 'Enter this secret manually in the authenticator app:' . PHP_EOL;
    $output .= $secret . PHP_EOL;
    $output .= provisioning_uri($secret, $account) . PHP_EOL;
    $output .= 'Or scan this code from the terminal:' . PHP_EOL;
    $output .= qr_text(provisioning_uri($secret, $account)) . PHP_EOL;

    return [$output, 0];
}

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function disable_command(Config $config, string $username = ''): array
{
    $account = resolve_user($config, $username);

    if ($account === null) {
        return [user_error($config, $username), 1];
    }

    // null removes the field entirely, so the record does not keep a secret that is
    // switched off; an empty string would still be a value in the file
    if (!write_user_field($config->appDir, $account, 'totpSecret', null)) {
        return ["Could not write settings.\n", 1];
    }

    return [sprintf('Two-factor authentication is now disabled for "%s".', $account) . PHP_EOL, 0];
}

/**
 * The named user, or null when the name is missing or unknown. The command never
 * guesses, not even in an installation with a single account: a second factor that
 * silently lands on the wrong record is exactly the kind of mistake this command
 * must not make.
 */
function resolve_user(Config $config, string $username): ?string
{
    if ($username === '') {
        return null;
    }

    return auth\find_user($config, $username) === null ? null : $username;
}

/**
 * Why resolve_user() could not decide, in words the caller can act on.
 */
function user_error(Config $config, string $username): string
{
    if ($username !== '') {
        return sprintf('Unknown user "%s".', $username) . PHP_EOL;
    }

    $names = array_keys($config->users);

    if (count($names) === 0) {
        return "No user is configured in settings.php.\n";
    }

    return 'Please name the user with --user, this installation has: ' . implode(', ', $names) . PHP_EOL;
}

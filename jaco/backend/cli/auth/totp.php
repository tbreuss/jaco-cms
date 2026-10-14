<?php declare(strict_types=1);

namespace jaco\cli\auth\totp;

use jaco\core\config\Config;

use function jaco\core\totp\generate_secret;
use function jaco\core\totp\provisioning_uri;
use function jaco\core\totp\qr_text;

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function setup_command(Config $config): array
{
    $secret = generate_secret();

    if (!store_secret($config, $secret)) {
        return ["Could not write settings.\n", 1];
    }

    $output = sprintf('Two-factor authentication is now required for "%s".', $config->username) . PHP_EOL;
    $output .= 'Enter this secret manually in the authenticator app:' . PHP_EOL;
    $output .= $secret . PHP_EOL;
    $output .= provisioning_uri($secret, $config->username) . PHP_EOL;
    $output .= 'Or scan this code from the terminal:' . PHP_EOL;
    $output .= qr_text(provisioning_uri($secret, $config->username)) . PHP_EOL;

    return [$output, 0];
}

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function disable_command(Config $config): array
{
    if (!store_secret($config, null)) {
        return ["Could not write settings.\n", 1];
    }

    return ["Two-factor authentication is now disabled.\n", 0];
}

// write_settings only stores strings, so merge here with var_export to
// keep the types (bool, int) of the existing settings untouched
function store_secret(Config $config, ?string $secret): bool
{
    $file = $config->appDir . '/settings.php';

    if (!is_readable($file)) {
        $settings = [];
    } else {
        $settings = require $file;
        if (!is_array($settings)) {
            return false;
        }
    }

    if ($secret === null) {
        unset($settings['totpSecret']);
    } else {
        $settings['totpSecret'] = $secret;
    }

    $content = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL;
    foreach ($settings as $key => $value) {
        $content .= '    ' . var_export($key, true) . ' => ' . var_export($value, true) . ',' . PHP_EOL;
    }
    $content .= '];' . PHP_EOL;

    return file_put_contents($file, $content) !== false;
}

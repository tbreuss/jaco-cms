<?php declare(strict_types=1);

// Browser based installer: upload Jaco, open this file, done. It writes
// jaco/settings.php and deletes itself afterwards.
//
// Deliberately plain PHP without any 8.x syntax: this file has to parse
// on older versions to report them instead of dying on them. Anyone who
// reaches this file before the owner can claim the installation, the
// same exposure every web installer has: it refuses to run once
// settings.php exists and removes itself on success.

$appDir = __DIR__;

function install_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function install_page(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . install_escape($title) . ' – Jaco CMS installer</title>'
        . '<style>body{max-width:40rem;margin:2rem auto;padding:0 1rem;font-family:system-ui,sans-serif}'
        . '.ok{color:green}.fail{color:red}table{border-collapse:collapse;width:100%}'
        . 'td,th{border:1px solid #ccc;padding:.4rem;text-align:left}'
        . 'input{display:block;width:100%;margin:.2rem 0 1rem;padding:.4rem;box-sizing:border-box}</style>'
        . '</head><body><h1>' . install_escape($title) . '</h1>' . $body . '</body></html>';
}

// Step 0 runs without any dependency: a missing vendor directory or an
// old PHP version must produce this page, not a fatal error. The version
// uses version_compare so analysis sees a real check: the composer
// platform pin only affects dependency resolution, the runtime PHP on a
// shared hoster can still be older.
$bootFailed = false;
$bootRows = '';
$bootChecks = [
    ['PHP 8.2 or newer', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION],
    ['Composer dependencies', is_file($appDir . '/vendor/autoload.php'), $appDir . '/vendor/autoload.php'],
];
foreach ($bootChecks as $check) {
    [$label, $ok, $detail] = $check;
    $bootFailed = $bootFailed || !$ok;
    $bootRows .= '<tr><td>' . install_escape($label) . '</td><td class="' . ($ok ? 'ok' : 'fail') . '">'
        . ($ok ? 'ok' : 'FAILED') . '</td><td>' . install_escape($detail) . '</td></tr>';
}

if ($bootFailed) {
    install_page('Jaco CMS installer', '<p>Installation cannot continue:</p>'
        . '<table><tr><th>Check</th><th></th><th>Detail</th></tr>' . $bootRows . '</table>');
    exit(1);
}

require $appDir . '/vendor/autoload.php';

use function jaco\core\auth\authenticate;
use function jaco\core\config\create_default_config;
use function jaco\core\config\write_settings;
use function jaco\core\filesystem\make_dir_rec;
use function jaco\core\system\install_checks;
use function jaco\core\totp\generate_secret;
use function jaco\core\totp\provisioning_uri;
use function jaco\core\totp\qr_svg;

if (is_file($appDir . '/settings.php')) {
    install_page(
        'Jaco CMS installer',
        '<p class="ok">Jaco is already installed.</p>'
            . '<p>The installer refuses to overwrite an existing installation. '
            . 'Delete <code>jaco/settings.php</code> by hand if you really want to start over.</p>'
            . '<p><a href="index.html">Open the admin panel</a></p>',
    );
    exit(0);
}

$config = create_default_config($appDir, []);

// Runtime directories may not exist in a fresh upload, create what is
// creatable before checking: the checks below verify the result.
@make_dir_rec($config, $appDir, '_backup');
@make_dir_rec($config, dirname($appDir), 'upload');
@make_dir_rec($config, $appDir, 'runtime/log');

$checks = install_checks($config);
$checksFailed = false;
$checkRows = '';
foreach ($checks as $check) {
    $checksFailed = $checksFailed || !$check['ok'];
    $checkRows .= '<tr><td>' . install_escape($check['label']) . '</td><td class="'
        . ($check['ok'] ? 'ok' : 'fail') . '">' . ($check['ok'] ? 'ok' : 'FAILED')
        . '</td><td>' . install_escape($check['detail']) . '</td></tr>';
}
$checksTable = '<table><tr><th>Check</th><th></th><th>Detail</th></tr>' . $checkRows . '</table>';

$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) && is_string($_POST['password']) ? (string) $_POST['password'] : '';
$language = isset($_POST['language']) && is_string($_POST['language']) ? $_POST['language'] : 'en';
$totp = !$isPost || (isset($_POST['totp']) && $_POST['totp'] === '1');
$errors = [];

if ($isPost && !$checksFailed) {
    if ($username === '') {
        $errors[] = 'Username cannot be blank.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password needs at least 8 characters.';
    }
    if ($language !== 'de' && $language !== 'en' && $language !== 'fr' && $language !== 'it') {
        $errors[] = 'Please select a language.';
    }

    if (count($errors) === 0) {
        try {
            $secret = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $secret = null;
        }

        $totpSecret = null;
        if ($totp) {
            try {
                $totpSecret = generate_secret();
            } catch (Throwable $e) {
                $totpSecret = null;
            }
        }

        if (!is_string($secret)) {
            $errors[] = 'Could not generate a secret key, please reload and try again.';
        } elseif ($totp && !is_string($totpSecret)) {
            $errors[] = 'Could not generate a two-factor secret, please reload and try again.';
        } elseif (!write_settings($appDir, array_merge(
            ['username' => $username, 'password' => $password, 'secretKey' => $secret, 'defaultLanguage' => $language],
            is_string($totpSecret) ? ['totpSecret' => $totpSecret] : [],
        ))) {
            $errors[] = 'Could not write jaco/settings.php, please check write permissions.';
        } else {
            $written = require $appDir . '/settings.php';
            $installed = create_default_config($appDir, is_array($written) ? $written : []);
            $token = authenticate($installed, $username, $password);

            if (!is_string($token)) {
                $errors[] = 'Settings written, but the login check failed. Please delete jaco/settings.php and start over.';
            } else {
                @unlink(__FILE__);
                $deleted = !is_file(__FILE__);
                $totpBlock = '';
                if (is_string($totpSecret)) {
                    $totpBlock = '<p class="ok">Two-factor authentication is enabled for login <strong>'
                        . install_escape($username) . '</strong>.</p>'
                        . '<p>Scan this code with the authenticator app, or enter the secret manually (it is also stored in <code>jaco/settings.php</code>):</p>'
                        . '<div style="max-width:220px">' . qr_svg(provisioning_uri($totpSecret, $username)) . '</div>'
                        . '<p><code>' . install_escape($totpSecret) . '</code></p>'
                        . '<p><code>' . install_escape(provisioning_uri($totpSecret, $username)) . '</code></p>';
                }
                install_page(
                    'Jaco CMS installed',
                    '<p class="ok">Login <strong>' . install_escape($username) . '</strong> works.</p>'
                        . $totpBlock
                        . ($deleted
                            ? '<p>The installer deleted itself as expected.</p>'
                            : '<p class="fail">The installer could NOT delete itself, please remove <code>jaco/install.php</code> by hand right now.</p>')
                        . '<p><a href="index.html">Open the admin panel</a></p>',
                );
                exit(0);
            }
        }
    }
}

$form = '';
if ($checksFailed) {
    $form = '<p class="fail">Fix the failed checks above, then reload this page.</p>';
} else {
    $errorList = '';
    foreach ($errors as $error) {
        $errorList .= '<li class="fail">' . install_escape($error) . '</li>';
    }
    $form = ($errorList === '' ? '' : '<ul>' . $errorList . '</ul>')
        . '<form method="post">'
        . '<label>Username<input name="username" value="' . install_escape($username) . '" autocomplete="username"></label>'
        . '<label>Password (at least 8 characters)<input type="password" name="password" autocomplete="new-password"></label>'
        . '<label>Language<select name="language">'
        . '<option value="de"' . ($language === 'de' ? ' selected' : '') . '>Deutsch</option>'
        . '<option value="en"' . ($language === 'en' ? ' selected' : '') . '>English</option>'
        . '<option value="fr"' . ($language === 'fr' ? ' selected' : '') . '>Français</option>'
        . '<option value="it"' . ($language === 'it' ? ' selected' : '') . '>Italiano</option>'
        . '</select></label>'
        . '<label><input type="checkbox" name="totp" value="1"' . ($totp ? ' checked' : '') . '> Enable two-factor authentication (needs an authenticator app)</label>'
        . '<button type="submit">Install</button></form>'
        . '<p>The secret key is generated for you and stored in <code>jaco/settings.php</code>.</p>';
}

install_page('Jaco CMS installer', $checksTable . $form);

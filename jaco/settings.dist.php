<?php declare(strict_types=1);

// Copy this file to settings.php and fill it in. Empty values are treated
// like missing ones: the app falls back to random credentials and logging in
// stays impossible until real values are set. The secretKey signs the login
// tokens, it needs at least 32 random characters - never deploy an example
// key, generate one (the browser installer does this for you).
//
// Roles: viewer (read), editor (write pages and uploads), admin (everything,
// including settings, CSS and backups). A user's password is a digest, and a
// record may also carry optional preferences:
//
//     'users' => [
//         'kunde' => [
//             'password' => '$2y$...',
//             'role' => 'viewer',
//             // ui language, empty falls back to defaultLanguage below
//             'language' => 'de',
//         ],
//         'demo' => [
//             'password' => '$2y$...',
//             'role' => 'viewer',
//             // a public demo must keep its seeded password, so visitors cannot
//             // lock each other out by changing it; missing means true
//             'passwordChange' => false,
//         ],
//         'admin' => [
//             'password' => '$2y$...',
//             'role' => 'admin',
//             // TOTP secret; missing or empty means no second factor for this user
//             'totpSecret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
//         ],
//     ],
//
// Every user has their own second factor. Enrol it with
// `php jaco/cli.php auth:totp-setup -u admin` and remove it with
// `auth:totp-disable -u admin`; do not share one secret between users. The
// password is a password_hash() digest, never a plaintext password. The
// browser installer stores it that way; when you fill it in by hand generate
// one with:
//
//     php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"

return [
    // users and roles: viewer (read), editor (write pages and uploads), admin
    // (everything, including settings, CSS and backups). The password is a digest.
    'users' => [
        // 'admin' => ['password' => '$2y$...', 'role' => 'admin'],
    ],
    'secretKey' => '',
    'uploadDir' => 'upload',
    'backupRetentionDays' => 90,
    'maintenanceMode' => false,
    'defaultLanguage' => 'en',
];

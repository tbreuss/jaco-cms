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
//             'password' => '<password-hash>',
//             'role' => 'viewer',
//             // ui language, empty falls back to defaultLanguage below
//             'language' => 'de',
//         ],
//         'demo' => [
//             'password' => '<password-hash>',
//             'role' => 'viewer',
//             // a public demo must keep its seeded password, so visitors cannot
//             // lock each other out by changing it; missing means true
//             'passwordChange' => false,
//         ],
//         'admin' => [
//             'password' => '<password-hash>',
//             'role' => 'admin',
//             // TOTP secret; missing or empty means no second factor for this user
//             'totpSecret' => '<base32-secret>',
//         ],
//     ],
//
// Directories, both relative to the web root: 'mediaDir' stores editor
// uploads (default 'media'), 'dataDir' stores templates and backups
// (default 'jaco-data'). Keep them out of the jaco/ directory so replacing
// the application never touches site data.
//
// Every user has their own second factor. Enrol it with
// `php jaco/cli.php auth:totp-setup -u admin` and remove it with
// `auth:totp-disable -u admin`; do not share one secret between users. The
// password is a password hash, never a plaintext password. The
// browser installer stores it that way; when you fill it in by hand, generate
// one with:
//
//     php jaco/cli.php auth:password --ask
//
// Put the digest into that user's 'password' field and keep the rest of the
// record. Existing login tokens are not revoked by a password change.

return [
    // users and roles: viewer (read), editor (write pages and uploads), admin
    // (everything, including settings, CSS and backups). The password is a digest.
    'users' => [
        // 'admin' => ['password' => '<password-hash>', 'role' => 'admin'],
    ],
    'secretKey' => '',
    'mediaDir' => 'media',
    'dataDir' => 'jaco-data',
    'backupRetentionDays' => 90,
    'maintenanceMode' => false,
    'defaultLanguage' => 'en',
];

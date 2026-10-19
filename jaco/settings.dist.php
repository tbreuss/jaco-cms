<?php declare(strict_types=1);

// Copy this file to settings.php and fill it in. Empty values are treated
// like missing ones: the app falls back to random credentials and logging in
// stays impossible until real values are set. The secretKey signs the login
// tokens, it needs at least 32 random characters - never deploy an example
// key, generate one (the browser installer does this for you).
//
// Roles: viewer (read), editor (write pages and uploads), admin (everything,
// including settings, CSS and backups). Without a users list the username and
// password below count as the admin. A user's password is a digest too:
//
//     'users' => [
//         'kunde' => ['password' => '$2y$...', 'role' => 'viewer'],
//     ],
// The password is a password_hash() digest, never a plaintext password. The
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
    'totpSecret' => '',
    'uploadDir' => 'upload',
    'backupRetentionDays' => 90,
    'maintenanceMode' => false,
    'defaultLanguage' => 'en',
];

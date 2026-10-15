<?php declare(strict_types=1);

// Copy this file to settings.php and fill it in. Empty values are treated
// like missing ones: the app falls back to random credentials and logging in
// stays impossible until real values are set. The secretKey signs the login
// tokens, it needs at least 32 random characters - never deploy an example
// key, generate one (the browser installer does this for you).

return [
    'username' => '',
    'password' => '',
    'secretKey' => '',
    'totpSecret' => '',
    'backupRetentionDays' => 90,
    'maintenanceMode' => false,
    'defaultLanguage' => 'en',
];

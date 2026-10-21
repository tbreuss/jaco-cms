<?php declare(strict_types=1);

// Write a disposable test configuration for the Playwright smoke tests. The
// file is gitignored (like the real settings.php) and must never be deployed.
// The password for every test account is "test-password".
$settings = [
    'users' => [
        'admin' => [
            'password' => password_hash('test-password', PASSWORD_DEFAULT),
            'role' => 'admin',
        ],
        'editor' => [
            'password' => password_hash('test-password', PASSWORD_DEFAULT),
            'role' => 'editor',
        ],
        'viewer' => [
            'password' => password_hash('test-password', PASSWORD_DEFAULT),
            'role' => 'viewer',
        ],
    ],
    'secretKey' => bin2hex(random_bytes(32)),
    'defaultLanguage' => 'en',
    'maintenanceMode' => false,
];

$file = __DIR__ . '/../../settings.php';
$content = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL
    . 'return ' . var_export($settings, true) . ';' . PHP_EOL;

if (file_put_contents($file, $content) === false) {
    fwrite(STDERR, 'Could not write ' . $file . PHP_EOL);
    exit(1);
}

echo 'Wrote test settings to ' . $file . PHP_EOL;

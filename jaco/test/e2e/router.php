<?php declare(strict_types=1);

// Test-server only: emulate the public site's 404 handling for PHP's built-in
// server, which does not read .htaccess or Nginx configuration.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$root = dirname(__DIR__, 3);
$target = $root . $path;

if ($path === '/' || is_file($target) || is_dir($target)) {
    return false;
}

http_response_code(404);
readfile($root . '/404.html');

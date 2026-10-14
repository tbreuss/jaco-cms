<?php declare(strict_types=1);

namespace jaco\core\totp;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Output\QRStringText;

// Time-based one-time passwords (RFC 6238) for the optional second
// login step. Offline by design: no SMS gateway, no mail, no network.

const PERIOD = 30;
const DIGITS = 6;

function generate_secret(int $bytes = 20): string
{
    $bytes = max(1, $bytes);

    return base32_encode(\random_bytes($bytes));
}

function base32_encode(string $bytes): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($bytes) as $char) {
        $bits .= str_pad(decbin(\ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $encoded = '';
    foreach (str_split($bits, 5) as $chunk) {
        // 5 bits always fit into an int, bindec may still return a float
        $encoded .= $alphabet[(int) bindec(str_pad($chunk, 5, '0'))];
    }

    return $encoded;
}

function base32_decode(string $encoded): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $encoded = strtoupper(rtrim($encoded, '='));
    $bits = '';
    foreach (str_split($encoded) as $char) {
        $position = strpos($alphabet, $char);
        if ($position === false) {
            return '';
        }
        $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
    }
    $decoded = '';
    foreach (str_split($bits, 8) as $chunk) {
        if (\strlen($chunk) < 8) {
            break;
        }
        // 8 bits always fit into an int, bindec may still return a float
        $decoded .= \chr((int) bindec($chunk));
    }

    return $decoded;
}

function code_at(string $secret, int $timestamp): string
{
    $counter = (int) floor($timestamp / PERIOD);
    $hash = \hash_hmac('sha1', pack('J', $counter), base32_decode($secret), true);
    $offset = \ord($hash[19]) & 0x0f;
    $code = ((\ord($hash[$offset]) & 0x7f) << 24
        | (\ord($hash[$offset + 1]) & 0xff) << 16
        | (\ord($hash[$offset + 2]) & 0xff) << 8
        | (\ord($hash[$offset + 3]) & 0xff)) % (10 ** DIGITS);

    return str_pad((string) $code, DIGITS, '0', STR_PAD_LEFT);
}

function verify_code(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
{
    if ($secret === '' || $code === '') {
        return false;
    }
    $timestamp ??= time();
    for ($step = -$window; $step <= $window; $step++) {
        if (\hash_equals(code_at($secret, $timestamp + $step * PERIOD), $code)) {
            return true;
        }
    }

    return false;
}

function provisioning_uri(string $secret, string $account, string $issuer = 'Jaco CMS'): string
{
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
        . '?secret=' . $secret
        . '&issuer=' . rawurlencode($issuer)
        . '&digits=' . DIGITS
        . '&period=' . PERIOD;
}

// QR code as inline SVG markup for the browser based installer
function qr_svg(string $uri): string
{
    $options = new QROptions([
        'outputInterface' => QRMarkupSVG::class,
        'outputBase64' => false,
    ]);
    $svg = (new QRCode($options))->render($uri);

    return \is_string($svg) ? $svg : '';
}

// QR code as plain text for the terminal, scannable from the screen
function qr_text(string $uri): string
{
    $options = new QROptions(['outputInterface' => QRStringText::class]);
    $text = (new QRCode($options))->render($uri);

    return \is_string($text) ? $text : '';
}

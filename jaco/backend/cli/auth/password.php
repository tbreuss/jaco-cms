<?php declare(strict_types=1);

namespace jaco\cli\auth\password;

// Deliberately omits I, O, 0 and 1: the generated password is meant to be
// written down or typed once, and those characters are the usual source of
// transcription mistakes.
const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
const PASSWORD_LENGTH = 24;

/**
 * @param string|null $password null generates a random password; a value hashes
 *                              the password supplied by the operator
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function command(?string $password = null): array
{
    if ($password === '') {
        return ["Password must not be empty.\n", 1];
    }

    $generated = $password === null;
    if ($password === null) {
        $password = generate_password();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $output = $generated ? 'Generated password: ' . $password . PHP_EOL : '';
    $output .= 'Password hash: ' . $hash . PHP_EOL;

    return [$output, 0];
}

/**
 * A cryptographically random password from the unambiguous alphabet.
 */
function generate_password(int $length = PASSWORD_LENGTH): string
{
    if ($length < 12) {
        throw new \InvalidArgumentException('The password length must be at least 12.');
    }

    $max = strlen(ALPHABET) - 1;
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= ALPHABET[random_int(0, $max)];
    }

    return $password;
}

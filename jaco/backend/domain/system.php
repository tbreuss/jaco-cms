<?php

declare(strict_types=1);

namespace jaco\domain\system;

use jaco\auth;
use jaco\data;
use jaco\domain;
use jaco\system;

use const jaco\APP_VERSION;

function authenticate(array $input): array
{
    $errors = data\validate($input, [
        ['username', 'required'],
        ['username', 'string'],
        ['password', 'required'],
        ['password', 'string'],
    ]);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    $token = auth\authenticate($input['username'], $input['password']);

    if ($token === null) {
        return domain\invalid([['', 'Authentication failed.']]);
    }

    return domain\success(['token' => $token]);
}

function environment(): array
{
    return domain\success([
        system\defined_classes(),
        system\defined_constants(),
        system\defined_functions(),
    ]);
}

function version(): array
{
    return domain\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

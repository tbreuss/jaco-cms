<?php

declare(strict_types=1);

namespace jaco\service\system;

use jaco\auth;
use jaco\data;
use jaco\service;
use jaco\system;

use const jaco\APP_VERSION;

function authenticate(array $input): array
{
    $data = data\filter($input, [
        ['username', 'string'],
        ['password', 'string'],
    ]);

    $errors = data\validate($data, [
        ['username', 'required'],
        ['password', 'required'],
    ]);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    $token = auth\authenticate($data['username'], $data['password']);

    if ($token === null) {
        return service\invalid([['', 'Authentication failed.']]);
    }

    return service\success(['token' => $token]);
}

function environment(): array
{
    return service\success([
        system\defined_classes(),
        system\defined_constants(),
        system\defined_functions(),
    ]);
}

function version(): array
{
    return service\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

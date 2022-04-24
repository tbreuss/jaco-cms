<?php

namespace jaco\actions\login;

use jaco\auth;
use jaco\request;

function main(): array
{
    $data = request\post_values();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    return [
        'token' => auth\login($username, $password),
    ];
}

<?php

namespace jaco\actions\login;

use jaco\auth;
use jaco\request;
use jaco\validate;

function main(): array
{
    $data = request\post_values();
    $errors = validate\all($data, rules());
    if (count($errors) > 0) {
        return [422, $errors];
    }

    try {
        $token = auth\login($data['username'], $data['password']);
    } catch (\Throwable $t) {
        return [422, [['source' => ['pointer' => ''], 'detail' => $t->getMessage()]]];
    }

    return [
        'token' => $token,
    ];
}

function rules(): array
{
    return [
        ['username', 'required'],
        ['password', 'required'],
    ];
}

<?php

namespace jaco\validate;

use jaco\file;
use jaco\models\page;

function numeric(mixed $value): array
{
    $status = is_numeric($value);
    if ($status) {
        return [true, ''];
    }
    return [false, '{value} must be an number.'];
}

function string(mixed $value): array
{
    $status = is_string($value);
    if ($status) {
        return [true, ''];
    }
    return [false, '{value} must be a string.'];
}

function required(mixed $value): array
{
    if (is_null($value) || (is_string($value) && trim($value) === '')) {
        return [false, '{value} cannot be blank.'];
    }
    return [true, ''];
}

function safe(): array
{
    return [true, ''];
}

function test(mixed $value, int $avg, int $min, int $max, int $sum): array
{
    $test = func_get_args();
    return [true, ''];
}

function file_html(mixed $value): array
{
    if (page\is_valid($value)) {
        return [true, ''];
    }
    return [false, '{value} is invalid.'];
}

function file_writeable(mixed $value): array
{
    if (file\is_writable($value)) {
        return [true, ''];
    }
    return [false, '{value} is not writeable.'];
}

function file_exists(mixed $value): array
{
    if (file\exists($value)) {
        return [true, ''];
    }
    return [false, '{value} does not exist.'];
}

function file_not_exists(mixed $value): array
{
    if (!file\exists($value)) {
        return [true, ''];
    }
    return [false, '{value} exists but must not exist.'];
}

function one(): void
{
    // TODO
}

function all(array $data, array $rules): array
{
    $errors = [];
    $failed = [];
    foreach ($rules as $rule) {
        $name = $rule[0];
        if (in_array($name, $failed)) {
            continue;
        }
        $value = $data[$name] ?? null;
        $callable = 'jaco\\validate\\' . $rule[1];
        $options = array_slice($rule, 2);
        $options['value'] = $value;
        [$status, $message] = call_user_func_array($callable, $options);
        if ($status === true) {
            continue;
        }
        $errors[] = [
            'source' => ['pointer' => $name],
            'detail' =>  str_replace('{value}', $value, $message)
        ];
        $failed[] = $name;
    }
    return $errors;
}

/*
$data = [
    'test' => 'abc',
    'number' => 123,
    'string' => null,
];
print_r($data);

$rules = [
    ['test', 'required'],
    ['test', 'string'],
    ['number', 'numeric'],
    ['string', 'required'],
    ['string2', 'required'],
    ['test', 'test', 'min' => 0, 'max' => 100, 'avg' => 50, 'sum' => 150],

];

$errors = validate_array($data, $rules);

print_r($errors);
exit;
*/

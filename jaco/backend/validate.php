<?php

declare(strict_types=1);

namespace jaco\validate;

use jaco\filesystem\file;

function numeric(mixed $value): array
{
    if (is_numeric($value)) {
        return [true, ''];
    }
    return [false, '{value} must be a number.'];
}

function string(mixed $value): array
{
    if (is_string($value)) {
        return [true, ''];
    }
    return [false, '{value} must be a string.'];
}

function required(mixed $value): array
{
    if (is_string($value) && trim($value) !== '') {
        return [true, ''];
    }
    return [false, '{value} cannot be blank.'];
}

function safe(): array
{
    return [true, ''];
}

function test(mixed $value, int $avg, int $min, int $max, int $sum): array
{
    return [true, ''];
}

function htmlfile_extension(mixed $value): array
{
    if (is_string($value) && file\validate_extension(JACO_WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid extension'];
}

function htmlfile_name(mixed $value, bool $isBackup = false): array
{
    if (is_string($value) && file\validate_filename(JACO_WEB_DIR, $value, $isBackup)) {
        return [true, ''];
    }
    return [false, '{value} is an invalid filename'];
}

function file_writeable(mixed $value): array
{
    if (is_string($value) && file\is_writable(JACO_WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} is not writeable.'];
}

function file_exists(mixed $value, string $dir = JACO_WEB_DIR): array
{
    if (is_string($value) && file\exists($dir, $value)) {
        return [true, ''];
    }
    return [false, '{value} does not exist.'];
}

function file_not_exists(mixed $value): array
{
    if (!file\exists(JACO_WEB_DIR, $value)) {
        return [true, ''];
    }
    return [false, '{value} must not exist.'];
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
            'detail' =>  str_replace('{value}', $value ?? $name, $message)
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

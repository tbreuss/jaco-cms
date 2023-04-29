<?php

declare(strict_types=1);

namespace jaco\data;

function filter(array $data, array $filters): array
{
    $filtered = [];
    foreach ($filters as $rule) {
        $name = $rule[0];
        $value = $data[$name] ?? null;
        $callable = 'jaco\\data\\filter\\' . $rule[1];
        $options = array_slice($rule, 2);
        $options['value'] = $value;
        $filteredValue = call_user_func_array($callable, $options);
        if ($filteredValue === null) {
            continue;
        }
        $filtered[$name] = $filteredValue;
    }
    return $filtered;
}

function filter_one(string|float|int|bool|null $var, array $filters): string|float|int|bool|null
{
    $name = '__name__';
    $data = [$name => $var];

    $filters = array_map(function($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $filters);

    $filtered = filter($data, $filters);

    return $filtered[$name] ?? null;
}

function validate(array $data, array $rules): array
{
    $errors = [];
    $failed = [];
    foreach ($rules as $rule) {
        $name = $rule[0];
        if (in_array($name, $failed)) {
            continue;
        }
        $value = $data[$name] ?? null;
        $callable = 'jaco\\data\\validator\\' . $rule[1];
        $options = array_slice($rule, 2);
        $options['value'] = $value;
        [$status, $message] = call_user_func_array($callable, $options);
        if ($status === true) {
            continue;
        }
        $errors[] = [
            $name,
            str_replace('{value}', (string)($value ?: 'Value'), $message)
        ];
        $failed[] = $name;
    }
    return $errors;
}

function validate_one(string|float|int|bool|null $var, string $name, array $rules): array
{
    $data = [$name => $var];

    $rules = array_map(function($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $rules);

    return validate($data, $rules);
}

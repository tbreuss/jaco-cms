<?php

declare(strict_types=1);

namespace jaco\data;

/**
 * @phpstan-param array<string,mixed> $data
 * @phpstan-param array<int,mixed> $filters
 * @return array<string,mixed>
 */
function filter(array $data, array $filters): array
{
    $filtered = [];
    foreach ($filters as $rule) {
        $name = $rule[0];
        $value = $data[$name] ?? null;
        $callable = $rule[1];
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

/**
 * @phpstan-param array<string,string> $data
 * @phpstan-param array<array<string>> $rules
 * @phpstan-return array<int,array<string,array<string,string>|string>>
 */
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
        $callable = $rule[1];
        $options = array_slice($rule, 2);
        $options['value'] = $value;
        [$status, $message] = call_user_func_array($callable, $options);
        if ($status === true) {
            continue;
        }
        $errors[] = [
            'source' => ['pointer' => $name],
            'title' => 'Validation Error',
            'detail' => str_replace('{value}', $value ?: $name, $message)
        ];
        $failed[] = $name;
    }
    return $errors;
}

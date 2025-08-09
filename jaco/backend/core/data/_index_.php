<?php declare(strict_types=1);

namespace jaco\core\data;

function sanitize(array $data, array $filters): array
{
    $filtered = [];
    foreach ($filters as $rule) {
        $name = $rule[0];
        $value = $data[$name] ?? null;
        $callable = 'jaco\\core\\data\\sanitize\\' . $rule[1];
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

function sanitize_one(string|float|int|bool|null $var, array $filters): string|float|int|bool|null
{
    $name = '__name__';
    $data = [$name => $var];

    $filters = array_map(function ($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $filters);

    $filtered = sanitize($data, $filters);

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
        $callable = 'jaco\\core\\data\\validate\\' . $rule[1];
        $options = array_slice($rule, 2);
        $options['value'] = $value;
        /** @var data\validate\Result $result */
        $result = call_user_func_array($callable, $options);
        if ($result->isValid) {
            continue;
        }
        $errors[] = [
            $name,
            str_replace('{value}', (string) ($value ?: 'Value'), $result->message),
        ];
        $failed[] = $name;
    }
    return $errors;
}

function validate_one(string|float|int|bool|null $var, string $name, array $rules): array
{
    $data = [$name => $var];

    $rules = array_map(function ($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $rules);

    return validate($data, $rules);
}

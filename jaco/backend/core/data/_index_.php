<?php declare(strict_types=1);

namespace jaco\core\data;

use InvalidArgumentException;
use jaco\core\data\validate\Result;

/**
 * Sanitizes the given fields with the named filter functions.
 *
 * The parameter type lists the filters this helper supports, all of them
 * return a string or null. The sanitize namespace also holds sanitizers with
 * other return types (integer, float), they are not part of the contract here
 * because every sanitized value of the cms is a string.
 *
 * @param array<string, mixed> $data
 * @param list<array{0: string, 1: 'filename'|'string', ...}> $filters
 * @return array<string, string>
 */
function sanitize(array $data, array $filters): array
{
    $filtered = [];
    foreach ($filters as $rule) {
        $name = $rule[0];
        $value = $data[$name] ?? null;
        $callable = 'jaco\\core\\data\\sanitize\\' . $rule[1];
        if (!function_exists($callable)) {
            throw new InvalidArgumentException('Unknown sanitizer: ' . $callable);
        }
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
 * @param list<array{0: 'filename'|'string', ...}> $filters
 */
function sanitize_one(string|float|int|bool|null $var, array $filters): ?string
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

/**
 * @param array<string, mixed> $data
 * @param array<array-key, mixed> $rules rule name, validator name and its options
 * @return list<array{string, string}>
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
        $callable = 'jaco\\core\\data\\validate\\' . $rule[1];
        if (!function_exists($callable)) {
            throw new InvalidArgumentException('Unknown validator: ' . $callable);
        }
        $options = array_slice($rule, 2);
        $options['value'] = $value;
        /** @var Result $result */
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

/**
 * @param array<array-key, mixed> $rules validator name and its options
 * @return list<array{string, string}>
 */
function validate_one(string|float|int|bool|null $var, string $name, array $rules): array
{
    $data = [$name => $var];

    $rules = array_map(function ($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $rules);

    return validate($data, $rules);
}

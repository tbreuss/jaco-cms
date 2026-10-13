<?php declare(strict_types=1);

/**
 * Entry point of the data helpers. sanitize() and validate() dispatch to the
 * filter functions in the sanitize/ and validate/ namespaces, the maps inside
 * them are the full list of supported filters.
 */

namespace jaco\core\data;

use InvalidArgumentException;
use jaco\core\data\validate\Result;

/**
 * Sanitizes the given fields with the named filter functions.
 *
 * The map below lists the filters this helper supports, all of them
 * return a string. The sanitize namespace also holds sanitizers with
 * other return types (integer, float), they are not part of the contract here
 * because every sanitized value of the cms is a string. Unknown names are
 * rejected at runtime, the parameter type stays open on purpose so the
 * guard remains reachable for analysis and tests.
 *
 * @param array<string, mixed> $data
 * @param list<array{0: string, 1: string, ...}> $filters
 * @return array<string, string>
 */
function sanitize(array $data, array $filters): array
{
    // first class callables instead of a concatenated function name: a typo
    // in a value is a phpstan error, a typo in a rule is still caught by
    // the null coalescing at runtime
    $sanitizers = [
        'filename' => sanitize\filename(...),
        'string' => sanitize\string(...),
    ];

    $filtered = [];
    foreach ($filters as $rule) {
        $name = $rule[0];
        $value = $data[$name] ?? null;
        $callable = $sanitizers[$rule[1]]
            ?? throw new InvalidArgumentException('Unknown sanitizer: ' . $rule[1]);
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
 * @param list<array{0: string, ...}> $filters filter name and its options, the field name is added here
 */
function sanitize_value(string|float|int|bool|null $var, array $filters): string
{
    $name = '__name__';
    $data = [$name => $var];

    $filters = array_map(function ($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $filters);

    $filtered = sanitize($data, $filters);

    // the supported sanitizers never return null, so the field is always set;
    // an empty string lets the required rule report the problem to the user
    return $filtered[$name] ?? '';
}

/**
 * @param array<string, mixed> $data
 * @param array<array-key, mixed> $rules rule name, validator name and its options
 * @return list<array{string, string}>
 */
function validate(array $data, array $rules): array
{
    // first class callables instead of a concatenated function name: a typo
    // in a value is a phpstan error, a typo in a rule is still caught by
    // the null coalescing at runtime
    $validators = [
        'file_exists' => validate\file_exists(...),
        'file_extension' => validate\file_extension(...),
        'file_in_dir' => validate\file_in_dir(...),
        'file_writeable' => validate\file_writeable(...),
        'html_file_extension' => validate\html_file_extension(...),
        'html_file_name' => validate\html_file_name(...),
        'numeric' => validate\numeric(...),
        'required' => validate\required(...),
        'safe' => validate\safe(...),
        'sort_parameter' => validate\sort_parameter(...),
        'string' => validate\string(...),
    ];

    $errors = [];
    $failed = [];
    foreach ($rules as $rule) {
        $name = $rule[0];
        if (in_array($name, $failed)) {
            continue;
        }
        $value = $data[$name] ?? null;
        $callable = $validators[$rule[1]]
            ?? throw new InvalidArgumentException('Unknown validator: ' . $rule[1]);
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
function validate_value(string|float|int|bool|null $var, string $name, array $rules): array
{
    $data = [$name => $var];

    $rules = array_map(function ($rule) use ($name) {
        array_unshift($rule, $name);
        return $rule;
    }, $rules);

    return validate($data, $rules);
}

<?php

declare(strict_types=1);

namespace jaco\service;

use jaco\jsonschema;

function call(callable $service, array $arguments): array
{
    $function = new \ReflectionFunction($service);

    $attributes = [];
    foreach ($function->getAttributes() as $attribute) {
        $attributeName = $attribute->getName();
        $attributes[$attributeName] = match($attributeName) {
            'jaco\validate\input', 'jaco\validate\output' => $attribute->getArguments()[0] ?? null,
            default => null
        };
    }

    $errors = [];
    if (isset($attributes['jaco\validate\input'])) {
        $errors = validate_input($arguments, $attributes['jaco\validate\input']);
    }

    $output = $service($arguments, $errors);

    if (isset($attributes['jaco\validate\output'])) {
        $errors = validate_output($arguments, $attributes['jaco\validate\output']);
        if (count($errors) > 0) {
            throw new \exception('JSON schema validation of service output failed');
        }
    }

    return $output;
}

function validate_input(array $input, string $schema): array
{
    return jsonschema\validate_service($input, $schema);
}

function validate_output(array $output, string $schema): array
{
    return jsonschema\validate_service($output, $schema);
}

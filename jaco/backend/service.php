<?php

declare(strict_types=1);

namespace jaco\service;

use jaco\jsonschema;

use const jaco\api\backup\delete\input_mapping;
use const jaco\api\page\copy\output_mapping;
use const jaco\service\backup\ERROR;
use const jaco\service\backup\INVALID;
use const jaco\service\backup\SUCCESS;

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

function call_from_api(callable $callable, array $apiInput, array $inputMapping, ?array $outputMapping = null): array
{
    $serviceInput = \jaco\service\transform_data($apiInput, $inputMapping);

    [$serviceStatus, $serviceOutput] = $callable($serviceInput);

    if ($serviceStatus === SUCCESS) {
        if ($serviceOutput === null) {
            return \jaco\response\ok($serviceOutput);
        }
        $apiOutput = \jaco\service\transform_data($serviceOutput, $outputMapping);
        return \jaco\response\ok($apiOutput);
    }

    if ($serviceStatus === INVALID) {
        $apiOutput = \jaco\service\transform_errors($serviceOutput, array_flip(input_mapping));
        return \jaco\response\unprocessable_entity($apiOutput);
    }

    if ($serviceStatus === ERROR) {
        $apiOutput = \jaco\service\transform_errors($serviceOutput, array_flip(input_mapping));
        return \jaco\response\internal_server_error($apiOutput);
    }

    return \jaco\response\internal_server_error(\jaco\service\invalid_service_status());
}

function validate_input(array $input, string $schema): array
{
    return jsonschema\validate_service($input, $schema);
}

function validate_output(array $output, string $schema): array
{
    return jsonschema\validate_service($output, $schema);
}

function transform_errors(array $errors, array $mapping): array
{
    $transformed = [];
    foreach ($errors as $error) {
        [$field, $message] = $error;
        $transformed[] = [
            'source' => [
                'pointer' => $mapping[$field] ?: ''
            ],
            'title' => 'Validation Error',
            'detail' => $message,
        ];
    }
    return $transformed;
}

function transform_data(array $data, array $mapping): array
{
    $mapped = [];
    foreach ($mapping as $from => $to) {
        $mapped[$to] = $data[$from];
    }
    return $mapped;
}

function invalid_service_status(): array
{
    return [['title' => 'Server Error', 'detail' => 'Invalid service status']];
}

<?php

declare(strict_types=1);

namespace jaco\lib\responder;

use jaco\domain;
use jaco\lib\http\response;

const VALIDATE_INPUT_ATTRIBUTE = 'jaco\lib\attribute\validate_input';
const VALIDATE_OUTPUT_ATTRIBUTE = 'jaco\lib\attribute\validate_output';

/**
 * @deprecated
 */
function call_service(callable $service, array $arguments): array
{
    $function = new \ReflectionFunction($service);

    $attributes = [];
    foreach ($function->getAttributes() as $attribute) {
        $attributeName = $attribute->getName();
        $attributes[$attributeName] = match ($attributeName) {
            VALIDATE_INPUT_ATTRIBUTE, VALIDATE_OUTPUT_ATTRIBUTE => $attribute->getArguments()[0] ?? null,
            default => null
        };
    }

    $errors = [];
    if (isset($attributes[VALIDATE_INPUT_ATTRIBUTE])) {
        $errors = domain\validate_input($arguments, $attributes[VALIDATE_INPUT_ATTRIBUTE]);
    }

    $output = $service($arguments, $errors);

    if (isset($attributes[VALIDATE_OUTPUT_ATTRIBUTE])) {
        $errors = domain\validate_output($arguments, $attributes[VALIDATE_OUTPUT_ATTRIBUTE]);
        if (count($errors) > 0) {
            throw new \exception('JSON schema validation of service output failed');
        }
    }

    return $output;
}

/**
 * @deprecated
 */
function call_service_from_api(
    callable $callable,
    array $apiInput,
    array $inputMapping,
    ?array $outputMapping = null
): array {
    $serviceInput = transform_data($apiInput, $inputMapping);

    [$serviceStatus, $serviceOutput] = call_service($callable, $serviceInput);

    if ($serviceStatus === domain\Payload::SUCCESS) {
        if ($serviceOutput === null) {
            return response\ok($serviceOutput);
        }
        $apiOutput = transform_data($serviceOutput, $outputMapping);
        return response\ok($apiOutput);
    }

    return handle_api_errors($serviceStatus, $serviceOutput, $inputMapping);
}

/**
 * @deprecated
 */
function handle_api_errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): array
{
    if ($serviceStatus === domain\Payload::INVALID) {
        $apiOutput = transform_client_errors($serviceOutput, array_flip($inputMapping));
        return response\unprocessable_entity($apiOutput);
    }

    if ($serviceStatus === domain\Payload::ERROR) {
        $apiOutput = transform_server_errors($serviceOutput, array_flip($inputMapping));
        return response\internal_server_error($apiOutput);
    }

    return response\internal_server_error(invalid_service_status());
}

/**
 * @deprecated
 */
function transform_data(array $data, array|callable $mapping): array
{
    if (is_callable($mapping)) {
        return $mapping($data);
    }

    $mapped = [];
    foreach ($mapping as $from => $to) {
        if (array_key_exists($from, $data)) {
            $mapped[$to] = $data[$from];
        }
    }

    return $mapped;
}

function api_errors(domain\Payload $payload, array $mapping = []): array
{
    if ($payload->isInvalid) {
        $output = transform_client_errors($payload->data, $mapping);
        return response\unprocessable_entity($output);
    }

    if ($payload->isError) {
        $output = transform_server_errors($payload->data, $mapping);
        return response\internal_server_error($output);
    }

    return response\internal_server_error(
        invalid_service_status()
    );
}

function cli_errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): void
{
    if ($serviceStatus === domain\Payload::SUCCESS) {
        exit(0);
    }

    if ($serviceStatus === domain\Payload::INVALID) {
        $cliOutput = transform_client_errors($serviceOutput, $inputMapping);
        render_errors($cliOutput);
        exit(1);
    }

    if ($serviceStatus === domain\Payload::ERROR) {
        $cliOutput = transform_server_errors($serviceOutput, $inputMapping);
        render_errors($cliOutput);
        exit(2);
    }

    $cliOutput = invalid_service_status();
    render_errors($cliOutput);
    exit(3);
}

function render_errors(array $errors): void
{
    foreach ($errors as $error) {
        fwrite(STDOUT, $error['title'] . ': ');
        fwrite(STDOUT, $error['detail'] . ' ');
        if (!empty($error['source']['pointer'])) {
            fwrite(STDOUT, '(' . $error['source']['pointer'] . ')');
        }
        fwrite(STDOUT, PHP_EOL);
    }
}

function transform_errors(array $errors, array|callable $mapping, string $title): array
{
    if (is_callable($mapping)) {
        return $mapping($errors, $title);
    }

    $transformed = [];
    foreach ($errors as $error) {
        [$field, $message] = $error;
        $transformed[] = [
            'source' => [
                'pointer' => $mapping[$field] ?? ''
            ],
            'title' => $title,
            'detail' => $message,
        ];
    }
    return $transformed;
}

function transform_client_errors(array $errors, array $mapping): array
{
    return transform_errors($errors, $mapping, 'Validation Error');
}

function transform_server_errors(array $errors, array $mapping): array
{
    return transform_errors($errors, $mapping, 'Server Error');
}

function invalid_service_status(): array
{
    return [['title' => 'Server Error', 'detail' => 'Invalid service status']];
}

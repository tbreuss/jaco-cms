<?php declare(strict_types=1);

namespace jaco\core\responder;

use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain;
use jaco\domain\Payload;

use function jaco\core\http\json_response;

use const jaco\MAINTENANCE_MODE;

/**
 * 503 response when maintenance mode blocks writing.
 * The flag defaults to the constant so tests can exercise both branches.
 */
function maintenance_response(?bool $enabled = null): ?Response
{
    if ($enabled ?? MAINTENANCE_MODE) {
        return json_response(
            [['title' => 'Maintenance mode: changes are disabled', 'detail' => 'Changes are disabled while maintenance mode is enabled.']],
            Status::SERVICE_UNAVAILABLE,
        );
    }

    return null;
}

/**
 * @param array<string, string> $mapping mapping of domain input variables to query parameters
 */
function api_errors(domain\Payload $payload, array $mapping = []): Response
{
    if ($payload->isInvalid) {
        $output = transform_client_errors($payload->data, $mapping);
        return json_response($output, Status::UNPROCESSABLE_ENTITY);
    }

    if ($payload->isError) {
        $output = transform_server_errors($payload->data, $mapping);
        return json_response($output, Status::INTERNAL_SERVER_ERROR);
    }

    return json_response(
        invalid_service_status(),
        Status::INTERNAL_SERVER_ERROR,
    );
}

function cli_errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): void
{
    if ($serviceStatus === Payload::SUCCESS) {
        exit(0);
    }

    if ($serviceStatus === Payload::INVALID) {
        $cliOutput = transform_client_errors($serviceOutput, $inputMapping);
        render_errors($cliOutput);
        exit(1);
    }

    if ($serviceStatus === Payload::ERROR) {
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
                'pointer' => $mapping[$field] ?? '',
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

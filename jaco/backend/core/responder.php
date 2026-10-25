<?php declare(strict_types=1);

namespace jaco\core\responder;

use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain;
use jaco\domain\Payload;

use function jaco\core\http\json_response;

/**
 * 503 response when maintenance mode blocks writing.
 * The flag defaults to the constant so tests can exercise both branches.
 */
function maintenance_response(?bool $enabled = null): ?Response
{
    if ($enabled ?? false) {
        return json_response(
            [['title' => 'Maintenance mode: changes are disabled', 'detail' => 'Changes are disabled while maintenance mode is enabled.']],
            Status::SERVICE_UNAVAILABLE,
        );
    }

    return null;
}

/**
 * 403 response when a read-only account tries to write. The account keeps its
 * role, so the routes and the editor work as usual - only storing is refused.
 */
function read_only_response(): Response
{
    return json_response(
        [['title' => 'Saving is disabled for this account', 'detail' => 'This account shows the interface without storing changes. Log in with a regular account to edit.']],
        Status::FORBIDDEN,
    );
}

/**
 * Reading routes a read-only account may not open: they would show the server
 * (paths, versions, environment) or the raw error log to a visitor of a public
 * demo.
 */
function demo_restricted_response(): Response
{
    return json_response(
        [['title' => 'Not available in the demo', 'detail' => 'This page shows details of the server. Open it with a regular account.']],
        Status::FORBIDDEN,
    );
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
        INVALID_SERVICE_STATUS_ERRORS,
        Status::INTERNAL_SERVER_ERROR,
    );
}

/**
 * Maps a domain status to cli output and exit code instead of exiting, so
 * commands stay testable: the single exit lives in cli.php.
 *
 * @return array{string, int} output and exit code
 */
function cli_errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): array
{
    if ($serviceStatus === Payload::SUCCESS) {
        return ['', 0];
    }

    if ($serviceStatus === Payload::INVALID) {
        $cliOutput = transform_client_errors($serviceOutput, $inputMapping);
        return [render_errors($cliOutput), 1];
    }

    if ($serviceStatus === Payload::ERROR) {
        $cliOutput = transform_server_errors($serviceOutput, $inputMapping);
        return [render_errors($cliOutput), 2];
    }

    $cliOutput = INVALID_SERVICE_STATUS_ERRORS;
    return [render_errors($cliOutput), 3];
}

function render_errors(array $errors): string
{
    $output = '';
    foreach ($errors as $error) {
        $output .= $error['title'] . ': ';
        $output .= $error['detail'] . ' ';
        if (!empty($error['source']['pointer'])) {
            $output .= '(' . $error['source']['pointer'] . ')';
        }
        $output .= PHP_EOL;
    }
    return $output;
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

const INVALID_SERVICE_STATUS_ERRORS = [
    ['title' => 'Server Error', 'detail' => 'Invalid service status'],
];

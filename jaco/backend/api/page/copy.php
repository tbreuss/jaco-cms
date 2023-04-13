<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\validate;
use jaco\request;
use jaco\response;
use jaco\service;
use jaco\struct\page;

/**
 * @phpstan-return array<int,mixed>
 */
function action_verbose(): array
{
    // API Schicht

    $apiInput = request\get_post_values();

    $apiErrors = request\validate($apiInput, '/api/page/copy/request.json');

    if (count($apiErrors) > 0) {
        return response\bad_request($apiErrors);
    }

    // Service Schicht

    $serviceInput = [
        'srcFile' => $apiInput['srcFileName'],
        'dstFile' => $apiInput['dstFileName'],
        'dstTitle' => $apiInput['dstPageTitle']
    ];

    [$serviceStatus, $serviceOutput] = service\page\copy_by_array($serviceInput);

    /*
    $srcFileName = $data['srcFileName'];
    $dstFileName = $data['dstFileName'];
    $dstPageTitle = $data['dstPageTitle'];

    [$serviceStatus, $serviceOutput] = page\copy_by_args($srcFileName, $dstFileName, $dstPageTitle);
    */

    if ($serviceStatus) {
        $apiOutput = [
            'file' => $serviceOutput->file,
            'title' => $serviceOutput->title,
            'created' => $serviceOutput->created
        ];

        $apiErrors = response\validate($apiOutput, '/api/page/copy/response.json');

        if (count($apiErrors) > 0) {
            return response\internal_server_error($apiErrors);
        }

        return response\created($apiOutput);
    }

    $transformedServiceErrors = transform_errors($serviceOutput, [
        'srcFile' => 'srcFileName',
        'dstFile' => 'dstFileName',
        'dstTitle' => 'dstPageTitle'
    ]);

    $apiErrors = response\validate($transformedServiceErrors, '/api/errors.json');

    if (count($apiErrors) > 0) {
        return response\internal_server_error($apiErrors);
    }

    return response\unprocessable_entity($transformedServiceErrors);
}

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/copy/request.json')]
#[validate\response('/api/page/copy/response.json')]
function action_attributes(array $data): array
{
    $serviceInput = [
        'srcFile' => $data['srcFileName'],
        'dstFile' => $data['dstFileName'],
        'dstTitle' => $data['dstPageTitle']
    ];

    [$serviceStatus, $serviceOutput] = service\page\copy_by_array($serviceInput);

    if ($serviceStatus) {
        $apiOutput = [
            'file' => $serviceOutput->file,
            'title' => $serviceOutput->title,
            'created' => $serviceOutput->created
        ];
        return response\created($apiOutput);
    }

    $apiErrors = transform_errors($serviceOutput, [
        'srcFile' => 'srcFileName',
        'dstFile' => 'dstFileName',
        'dstTitle' => 'dstPageTitle'
    ]);

    return response\unprocessable_entity($apiErrors);
}

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/copy/request.json')]
#[validate\response('/api/page/copy/response.json')]
function action(array $data): array
{
    [$serviceStatus, $serviceOutput] = service\page\copy_by_array([
        'srcFile' => $data['srcFileName'],
        'dstFile' => $data['dstFileName'],
        'dstTitle' => $data['dstPageTitle']
    ]);

    if ($serviceStatus === service\page\SUCCESS) {
        return response\created([
            'file' => $serviceOutput['file'],
            'title' => $serviceOutput['title'],
            'created' => $serviceOutput['created']
        ]);
    }

    if ($serviceStatus === service\page\INVALID) {
        return response\unprocessable_entity(
            transform_errors($serviceOutput, error_mapping())
        );
    }

    if ($serviceStatus === service\page\ERROR) {
        return response\internal_server_error(
            transform_errors($serviceOutput, error_mapping())
        );
    }

    return response\internal_server_error([]);
}

function error_mapping(): array
{
    return [
        'srcFile' => 'srcFileName',
        'dstFile' => 'dstFileName',
        'dstTitle' => 'dstPageTitle'
    ];
}

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/page/copy/request.json')]
#[validate\response('/api/page/copy/response.json')]
function action_service(array $data): array
{
    $input = [
        'srcFile' => $data['srcFileName'],
        'dstFile' => $data['dstFileName'],
        'dstTitle' => $data['dstPageTitle']
    ];

    [$status, $output] = service\call('jaco\service\page\copy_by_service', $input);

    if ($status) {
        /** @var page $output */
        return response\created([
            'file' => $output->file,
            'title' => $output->title,
            'created' => $output->created
        ]);
    } else {
        /** @var array $output */
        return response\unprocessable_entity(
            transform_errors($output, [
                'srcFile' => 'srcFileName',
                'dstFile' => 'dstFileName',
                'dstTitle' => 'dstPageTitle'
            ])
        );
    }
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

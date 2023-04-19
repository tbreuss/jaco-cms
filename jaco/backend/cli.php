<?php

declare(strict_types=1);

namespace jaco\cli;

use jaco\controller;
use jaco\service;

function transform_data(array $data, array|callable $mapping): array
{
    return controller\transform_data($data, $mapping);
}

function handle_errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): void
{
    if ($serviceStatus === service\SUCCESS) {
        exit(0);
    }

    if ($serviceStatus === service\INVALID) {
        $cliOutput = controller\transform_client_errors($serviceOutput, array_flip($inputMapping));
        render_errors($cliOutput);
        exit(1);
    }

    if ($serviceStatus === service\ERROR) {
        $cliOutput = controller\transform_server_errors($serviceOutput, array_flip($inputMapping));
        render_errors($cliOutput);
        exit(2);
    }

    $cliOutput = controller\invalid_service_status();
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

<?php

declare(strict_types=1);

namespace jaco\cli\responder;

use jaco\controller;
use jaco\domain;

function handle_errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): void
{
    if ($serviceStatus === domain\SUCCESS) {
        exit(0);
    }

    if ($serviceStatus === domain\INVALID) {
        $cliOutput = controller\transform_client_errors($serviceOutput, $inputMapping);
        render_errors($cliOutput);
        exit(1);
    }

    if ($serviceStatus === domain\ERROR) {
        $cliOutput = controller\transform_server_errors($serviceOutput, $inputMapping);
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

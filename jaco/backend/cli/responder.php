<?php

declare(strict_types=1);

namespace jaco\cli\responder;

use jaco\lib\responder;
use jaco\domain;

function errors(string $serviceStatus, array $serviceOutput, array $inputMapping = []): void
{
    if ($serviceStatus === domain\SUCCESS) {
        exit(0);
    }

    if ($serviceStatus === domain\INVALID) {
        $cliOutput = responder\transform_client_errors($serviceOutput, $inputMapping);
        render_errors($cliOutput);
        exit(1);
    }

    if ($serviceStatus === domain\ERROR) {
        $cliOutput = responder\transform_server_errors($serviceOutput, $inputMapping);
        render_errors($cliOutput);
        exit(2);
    }

    $cliOutput = responder\invalid_service_status();
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

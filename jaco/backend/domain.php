<?php

declare(strict_types=1);

namespace jaco\domain;

use jaco\json;

const ERROR = 'ERROR';
const INVALID = 'INVALID';
const SUCCESS = 'SUCCESS';

function error(array $errors): array
{
    return [ERROR, $errors];
}

function invalid(array $errors): array
{
    return [INVALID, $errors];
}

function success(array|string|null $data = null): array
{
    return [SUCCESS, $data];
}

function validate_input(array $input, string $schema): array
{
    return json\schema\validate_service($input, $schema);
}

function validate_output(array $output, string $schema): array
{
    return json\schema\validate_service($output, $schema);
}

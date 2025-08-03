<?php

declare(strict_types=1);

namespace jaco\domain;

use jaco\lib\json;

/**
 * @deprecated
 */
function validate_input(array $input, string $schema): array
{
    return json\schema\validate_service($input, $schema);
}

/**
 * @deprecated
 */
function validate_output(array $output, string $schema): array
{
    return json\schema\validate_service($output, $schema);
}

<?php

declare(strict_types=1);

namespace jaco\domain;

use jaco\lib\json;

class Payload
{
    public const ERROR = 'ERROR';
    public const INVALID = 'INVALID';
    public const SUCCESS = 'SUCCESS';
    public readonly bool $isError;
    public readonly bool $isInvalid;
    public readonly bool $isSuccess;

    public function __construct(
        readonly public string $status,
        readonly public mixed $data = null,
    ) {
        $this->isError = $this->status === self::ERROR;
        $this->isInvalid = $this->status === self::INVALID;
        $this->isSuccess = $this->status === self::SUCCESS;
    }

    public static function error(array $errors): Payload
    {
        return new static(self::ERROR, $errors);
    }

    public static function invalid(array $errors): Payload
    {
        return new static(self::INVALID, $errors);
    }

    public static function success(array|string|null $data = null): Payload
    {
        return new static(self::SUCCESS, $data);
    }
}

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

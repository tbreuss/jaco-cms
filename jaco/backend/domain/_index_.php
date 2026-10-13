<?php declare(strict_types=1);

namespace jaco\domain;

class Payload
{
    public const ERROR = 'ERROR';
    public const INVALID = 'INVALID';
    public const SUCCESS = 'SUCCESS';
    public readonly bool $isError;
    public readonly bool $isInvalid;
    public readonly bool $isSuccess;

    final public function __construct(
        public readonly string $status,
        public readonly mixed $data = null,
    ) {
        $this->isError = $this->status === self::ERROR;
        $this->isInvalid = $this->status === self::INVALID;
        $this->isSuccess = $this->status === self::SUCCESS;
    }

    public static function error(array $errors): static
    {
        return new static(self::ERROR, $errors);
    }

    public static function invalid(array $errors): static
    {
        return new static(self::INVALID, $errors);
    }

    public static function success(array|string|null $data = null): static
    {
        return new static(self::SUCCESS, $data);
    }
}

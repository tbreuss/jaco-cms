<?php declare(strict_types=1);

namespace jaco\core\attribute;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION)]
final class validate
{
    public const REQUEST = 1;
    public const RESPONSE = 1;

    public function __construct(
        public int $type,
        public string $schema,
    ) {}
}

#[Attribute]
final class validate_request
{
    public function __construct(
        public string $schema,
    ) {}
}

#[Attribute]
final class validate_response
{
    public function __construct(
        public string $schema,
    ) {}
}

#[Attribute]
final class validate_input
{
    public function __construct(
        public string $schema,
    ) {}
}

#[Attribute]
final class validate_output
{
    public function __construct(
        public string $schema,
    ) {}
}

<?php

declare(strict_types=1);

namespace jaco\attribute;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION)]
class validate
{
    const REQUEST = 1;
    const RESPONSE = 1;

    public function __construct(
        public int $type,
        public string $schema
    ) {
    }
}

#[Attribute]
class validate_request
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

#[Attribute]
class validate_response
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

#[Attribute]
class validate_input
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

#[Attribute]
class validate_output
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

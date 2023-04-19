<?php

declare(strict_types=1);

namespace jaco\attribute;

use Attribute;

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

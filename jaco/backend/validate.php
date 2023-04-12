<?php

declare(strict_types=1);

namespace jaco\validate;

use Attribute;

#[Attribute]
class request
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

#[Attribute]
class response
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

#[Attribute]
class input
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

#[Attribute]
class output
{
    private string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }
}

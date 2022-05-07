<?php

declare(strict_types=1);

namespace jaco\structs;

class file
{
    public function __construct(
        public string $file,
        public int $modified,
    ) {
    }
}

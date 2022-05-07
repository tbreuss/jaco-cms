<?php

declare(strict_types=1);

namespace jaco\structs;

class backup
{
    public function __construct(
        public string $file,
        public string $name,
        public string $title,
        public int $created,
    ) {
    }
}

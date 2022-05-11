<?php

declare(strict_types=1);

namespace jaco\struct;

class file
{
    public function __construct(
        public string $file,
        public int $modified,
    ) {
    }
}

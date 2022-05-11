<?php

declare(strict_types=1);

namespace jaco\struct;

class page
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
    ) {
    }
}

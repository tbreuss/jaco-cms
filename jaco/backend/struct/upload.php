<?php

declare(strict_types=1);

namespace jaco\struct;

class upload
{
    public function __construct(
        public string $file,
        public string $extension,
        public int $size,
        public int $created,
        public ?string $content
    ) {
    }
}

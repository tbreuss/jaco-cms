<?php

namespace jaco\structs;

class page
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
    ) {
    }
}

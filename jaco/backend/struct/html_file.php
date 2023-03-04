<?php

declare(strict_types=1);

namespace jaco\struct;
class html_file
{
    public function __construct(
        public string $file,
        public string $name,
        public string $title,
        public int $created,
        public string $content = ''
    ) {
    }
}

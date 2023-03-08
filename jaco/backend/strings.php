<?php

declare(strict_types=1);

namespace jaco\strings;

function random(int $length = 6): string
{
    return \substr(\str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

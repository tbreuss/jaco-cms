<?php

namespace jaco\view;

use jaco\asset;

function page(string $html): string
{
    $html = asset\inject_javascript($html, 'page.js');
    return $html;
}

<?php

namespace jaco\actions\page;

use jaco\page;
use jaco\view;

function main(string $file = '', string $default = JACO_DEFAULT_PAGE): string
{
    if (strlen($file) === 0) {
        header('Location: ?action=page&file=' . $default);
        exit;
    }
    $page = page\find_by_name($file);
    $html = page\get_html($page);
    return view\page($html);
}

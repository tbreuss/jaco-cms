<?php

declare(strict_types=1);

namespace jaco\models\template\page;

use jaco\filesystem\html_file;

function find_all(): array
{
    $files = html_file\find_htmlfiles(JACO_TEMPLATES_PAGES_DIR);
    return $files;
}

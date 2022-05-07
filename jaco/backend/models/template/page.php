<?php

declare(strict_types=1);

namespace jaco\models\template\page;

use jaco\filesystem\html_file;
use jaco\models\page;

function find_all(): array
{
    $files = html_file\find(JACO_TEMPLATES_PAGES_DIR);
    return page\create_list(JACO_TEMPLATES_PAGES_DIR, $files);
}

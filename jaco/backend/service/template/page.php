<?php

declare(strict_types=1);

namespace jaco\service\template\page;

use jaco\filesystem\html_file;
use jaco\service\page;

use const jaco\TEMPLATES_PAGES_DIR;

/**
 * @return \jaco\struct\page[]
 */
function find_all(): array
{
    $files = html_file\find(TEMPLATES_PAGES_DIR);
    return page\create_list(TEMPLATES_PAGES_DIR, $files);
}

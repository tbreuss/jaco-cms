<?php

declare(strict_types=1);

namespace jaco\model\template\page;

use jaco\filesystem\html_file;
use jaco\model\page;

/**
 * @return \jaco\struct\page[]
 */
function find_all(): array
{
    $files = html_file\find(JACO_TEMPLATES_PAGES_DIR);
    return page\create_list(JACO_TEMPLATES_PAGES_DIR, $files);
}

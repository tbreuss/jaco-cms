<?php

declare(strict_types=1);

namespace jaco\service\template\page;

use jaco\filesystem\html_file;
use jaco\service\page;

/**
 * @return \jaco\struct\page[]
 */
function find_all(): array
{
    $files = html_file\find(JACO_TEMPLATES_PAGES_DIR);
    return page\create_list(JACO_TEMPLATES_PAGES_DIR, $files);
}

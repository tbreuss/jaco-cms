<?php

declare(strict_types=1);

namespace jaco\service\template\page;

use jaco\filesystem\html_file;
use jaco\service\page;

use const jaco\TEMPLATE_PAGE_DIR;

/**
 * @return \jaco\struct\page[]
 */
function find_all(): array
{
    $files = html_file\find(TEMPLATE_PAGE_DIR);
    return page\create_list(TEMPLATE_PAGE_DIR, $files);
}

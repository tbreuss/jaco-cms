<?php

declare(strict_types=1);

namespace jaco\service\template\page;

use jaco\filesystem;
use jaco\service\page;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

/**
 * @return \jaco\struct\page[]
 */
function find_all(): array
{
    $files = filesystem\find_files(TEMPLATE_PAGE_DIR, PAGE_EXTENSIONS);
    return page\create_list(TEMPLATE_PAGE_DIR, $files);
}

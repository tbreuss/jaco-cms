<?php

namespace jaco\models\template\page;

use jaco\file;

function find_all(): array
{
    $files = file\find_htmlfiles(JACO_TEMPLATES_PAGES_DIR);
    return $files;
}

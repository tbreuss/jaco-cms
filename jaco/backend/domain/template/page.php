<?php

declare(strict_types=1);

namespace jaco\domain\template\page;

use jaco\data;
use jaco\filesystem;
use jaco\domain;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

function find_all(array $input): array
{
    $data = data\filter($input, []);

    $errors = data\validate($data, []);

    if (count($errors) > 0) {
        return domain\invalid($errors);
    }

    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return domain\error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(TEMPLATE_PAGE_DIR, PAGE_EXTENSIONS);
    $list = domain\page\_create_list($files, TEMPLATE_PAGE_DIR);

    return domain\success($list);
}

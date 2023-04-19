<?php

declare(strict_types=1);

namespace jaco\service\template\page;

use jaco\data;
use jaco\filesystem;
use jaco\service;

use const jaco\PAGE_EXTENSIONS;
use const jaco\TEMPLATE_PAGE_DIR;

function find_all(array $input): array
{
    $data = data\filter($input, []);

    $errors = data\validate($data, []);

    if (count($errors) > 0) {
        return service\invalid($errors);
    }

    if (!filesystem\dir_exists(TEMPLATE_PAGE_DIR)) {
        return service\error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(TEMPLATE_PAGE_DIR, PAGE_EXTENSIONS);
    $list = service\page\_create_list($files, TEMPLATE_PAGE_DIR);

    return service\success($list);
}

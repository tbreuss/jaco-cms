<?php

declare(strict_types=1);

namespace jaco\service\system;

use jaco\service;
use jaco\system;

use const jaco\APP_VERSION;

function environment(): array
{
    return service\success([
        system\defined_classes(),
        system\defined_constants(),
        system\defined_functions(),
    ]);
}

function version(): array
{
    return service\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

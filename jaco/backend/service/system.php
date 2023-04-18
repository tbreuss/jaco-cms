<?php

declare(strict_types=1);

namespace jaco\service\system;

use jaco\service;

use const jaco\APP_VERSION;

function version(): array
{
    return service\success([
        'name' => 'jaco',
        'version' => APP_VERSION
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\translation;

use jaco\attribute;
use jaco\controller;
use jaco\http\response;
use jaco\service;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/translation/response.json')]
function action(string $language): array
{
    [$serviceStatus, $serviceOutput] = service\translation\all($language);

    if ($serviceStatus === service\SUCCESS) {
        return response\ok($serviceOutput); // no output mapping needed
    }

    return controller\handle_api_errors($serviceStatus, $serviceOutput);
}

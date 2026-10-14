<?php declare(strict_types=1);

namespace jaco\cli\auth\unblock;

use jaco\core\auth;
use jaco\core\config\Config;

/**
 * Lifts every login block caused by failed attempts. Blocks are per address and expire
 * after ten minutes by themselves; this is for the case where that wait is in the way,
 * for example a shared office address.
 *
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function command(Config $config): array
{
    auth\clear_login_history($config);

    return ["Login blocks cleared for all addresses.\n", 0];
}

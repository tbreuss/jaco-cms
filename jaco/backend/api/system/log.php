<?php

declare(strict_types=1);

namespace jaco\api\system\log;

use jaco\attribute;
use jaco\http\response;

use const jaco\ERROR_LOG;

#[attribute\validate_response('/api/diagnostics/log/response.json')]
function action(): array
{
    $lines = [];

    if (\file_exists(ERROR_LOG)) {
        $fp = \fopen(ERROR_LOG, 'r');

        $pos = -2; // Skip final new line character (Set to -1 if not present)

        $i = 0;
        $currentLine = '';

        while (-1 !== \fseek($fp, $pos, SEEK_END)) {
            $char = \fgetc($fp);
            if (PHP_EOL == $char) {
                $lines[] = $currentLine;
                $currentLine = '';
                $i++;
                if ($i >= 1000) {
                    break;
                }
            } else {
                $currentLine = $char . $currentLine;
            }
            $pos--;
        }
        if (\trim($currentLine) !== '') {
            $lines[] = $currentLine; // Grab final line
        }
        \fclose($fp);
    }
    return response\ok($lines);
}

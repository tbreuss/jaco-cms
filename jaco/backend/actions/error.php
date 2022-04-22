<?php

namespace jaco\actions\error;

use jaco\html;

function main(?\Throwable $e = null): string
{
    if ($e === null) {
        return html\h('h1', 'Unknown Error');
    }
    $message = $e->getMessage();
    return html\h('div', [
        html\h('h1', $message ?: 'Error'),
        html\h('pre', $e->getTraceAsString())
    ]);
}

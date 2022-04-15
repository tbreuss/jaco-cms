<?php

namespace jaco\action;

use jaco\exception;
use jaco\page;
use jaco\html;

function page(?string $file = 'index.html'): string
{
    print_r($file);
    
    $page = page\find_by_name($file);

    print_r($page);

    return html\h('h1', 'Page');
}

function error(?exception\Exception $e = null): string
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

function login(): string
{
    return html\h('h1', 'Login');
}

function logout(): string
{
    return html\h('h1', 'Logout');
}

<?php

namespace jaco\action;

use jaco\exception;
use jaco\page;
use jaco\html;
use jaco\view;

function page(string $file = '', string $default = JACO_DEFAULT_PAGE): string
{
    if (strlen($file) === 0) {
        header('Location: ?action=page&file=' . $default);
        exit;    
    }    
    $page = page\find_by_name($file);
    $html = page\get_html($page);
    return view\page($html);
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

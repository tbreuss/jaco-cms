<?php

declare(strict_types=1);

namespace jaco\html;

function get_title(string $html): string
{
    $title = '';
    $dom = new \DOMDocument();
    \libxml_use_internal_errors(true);
    if ($dom->loadHTML($html)) {
        $list = $dom->getElementsByTagName("title");
        if ($list->length > 0) {
            $title = $list->item(0)->textContent;
        }
    }
    \libxml_clear_errors();
    return $title;
}

function set_title(string $contents, string $title): string
{
    $replaced = \preg_replace('/<title>(.*?)<\/title>/', '<title>' . $title . '</title>', $contents);
    if ($replaced === null) {
        return $contents;
    }
    return $replaced;
}

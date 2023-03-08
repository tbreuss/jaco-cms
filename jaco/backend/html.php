<?php

declare(strict_types=1);

namespace jaco\html;

use jaco\filesystem;

use const jaco\UPLOAD_DIR;
use const jaco\UPLOAD_URL;

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

function handle_embedded_data_url_images(string $html): array
{
    \libxml_use_internal_errors(true);

    $dom = new \DOMDocument();

    if (!$dom->loadHTML($html)) {
        return [false, ['Could not load HTML']];
    }

    $imgAttr = 'data-jaco-editable-image';
    $imgSrcAttr = 'src';
    $imgFileAttr = 'data-jaco-editable-image-file';

    $images = $dom->getElementsByTagName('img');

    $errors = [];

    foreach ($images as $el) {
        /** @var DOMElement $el */

        if (!$el->hasAttribute($imgAttr) || !$el->hasAttribute($imgSrcAttr) || !$el->hasAttribute($imgFileAttr)) {
            continue;
        }

        $imgSrc = $el->getAttribute($imgSrcAttr);
        $imgFile = $el->getAttribute($imgFileAttr);

        if (($pos = \strpos($imgSrc, ',')) === false) {
            continue;
        }

        $encodedData = \str_replace(' ', '+', \substr($imgSrc, $pos + 1));
        $decodedData = \base64_decode($encodedData);

        [$status, $newFile] = filesystem\write_file_without_overwrite(UPLOAD_DIR, $imgFile, $decodedData);

        if ($status === false) {
            $errors[] = 'Could not write file' . $imgFile;
        }

        $el->setAttribute('src', filesystem\join(UPLOAD_URL, $newFile));
        $el->removeAttribute($imgFileAttr);
    }

    if (\count($errors) > 0) {
        return [false, $errors];
    }

    $newHtml = $dom->saveHTML();

    if ($newHtml === false) {
        return [false, ['Could not save HTML']];
    }

    $errors = \libxml_get_errors();

    if (\count($errors) > 0) {
        \libxml_clear_errors();
        return [false, $errors];
    }

    return [true, $newHtml];
}

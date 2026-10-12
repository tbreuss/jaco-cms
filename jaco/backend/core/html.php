<?php declare(strict_types=1);

namespace jaco\core\html;

use jaco\core\config\Config;
use jaco\core\data\sanitize;
use jaco\core\filesystem;

function get_title(string $html): string
{
    $title = '';

    if ($html === '') {
        return $title;
    }

    $dom = new \DOMDocument();
    \libxml_use_internal_errors(true);
    // without a charset hint libxml assumes ISO-8859-1 and mangles non-ascii titles
    if ($dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html)) {
        $list = $dom->getElementsByTagName("title");
        if ($list->length > 0) {
            $title = $list->item(0)->textContent;
        }
    }
    \libxml_clear_errors();
    return $title;
}

function set_title(string $html, string $title): string
{
    $replaced = \preg_replace('/<title>(.*?)<\/title>/', '<title>' . \htmlspecialchars($title) . '</title>', $html);
    if ($replaced === null) {
        return $html;
    }
    return $replaced;
}

const ALLOWED_TAGS = [
    'a', 'abbr', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup',
    'dd', 'del', 'details', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'h1', 'h2',
    'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'mark', 'ol', 'p',
    'pre', 'q', 's', 'samp', 'small', 'span', 'strong', 'sub', 'summary', 'sup',
    'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr', 'u', 'ul', 'var',
];

const REMOVE_TAGS = [
    'applet', 'base', 'embed', 'iframe', 'link', 'math', 'meta', 'noscript', 'object',
    'script', 'style', 'svg', 'template',
];

const ALLOWED_ATTRIBUTES = [
    '*' => [
        'class',
        'id',
        'data-jaco-editable',
        'data-jaco-editable-line',
        'data-jaco-editable-link',
        'data-jaco-editable-image',
        'data-jaco-editable-image-extended',
        'data-jaco-content-list',
        'data-jaco-content-item',
        'data-jaco-fragment',
        'data-jaco-title',
        'data-jaco-pos',
    ],
    'a' => ['href', 'rel', 'target', 'title'],
    'img' => ['alt', 'height', 'src', 'title', 'width'],
    'td' => ['colspan', 'rowspan'],
    'th' => ['colspan', 'rowspan'],
];

function sanitize_html(string $html): string
{
    \libxml_use_internal_errors(true);

    $dom = new \DOMDocument();

    if (!$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html)) {
        \libxml_clear_errors();
        return '';
    }

    $body = $dom->getElementsByTagName('body')->item(0);

    if ($body !== null) {
        sanitize_node($body);
    }

    \libxml_clear_errors();

    if ($body === null) {
        return '';
    }

    $html = '';
    foreach ($body->childNodes as $child) {
        $html .= $dom->saveHTML($child);
    }

    return $html;
}

/**
 * Replace the body of a document with the given body, keeping doctype and head untouched.
 * A fragment without a body tag is returned as the body, which keeps pages that are
 * stored without a surrounding document working.
 */
function replace_body(string $document, string $body): string
{
    $count = 0;
    $replaced = \preg_replace_callback(
        '#(<body\b[^>]*>).*?</body>#is',
        fn(array $matches): string => $matches[1] . $body . '</body>',
        $document,
        1,
        $count,
    );

    if (($replaced === null) || ($count === 0)) {
        return $body;
    }

    return $replaced;
}

function sanitize_node(\DOMElement $el): void
{
    $tag = \strtolower($el->nodeName);

    $remove = [];
    foreach ($el->attributes as $attr) {
        if (!allowed_attribute($tag, \strtolower($attr->nodeName), $attr->nodeValue)) {
            $remove[] = $attr->nodeName;
        }
    }
    foreach ($remove as $name) {
        $el->removeAttribute($name);
    }

    $children = \iterator_to_array($el->childNodes);
    foreach ($children as $child) {
        if (!$child instanceof \DOMElement) {
            continue;
        }

        $childTag = \strtolower($child->nodeName);

        if (\in_array($childTag, REMOVE_TAGS, true)) {
            $el->removeChild($child);
            continue;
        }

        sanitize_node($child);

        if (!\in_array($childTag, ALLOWED_TAGS, true)) {
            // unwrap the element, keep its sanitized children
            while ($child->firstChild !== null) {
                $el->insertBefore($child->firstChild, $child);
            }
            $el->removeChild($child);
        }
    }
}

function allowed_attribute(string $tag, string $name, string $value): bool
{
    $allowed = \array_merge(ALLOWED_ATTRIBUTES['*'], ALLOWED_ATTRIBUTES[$tag] ?? []);

    if (!\in_array($name, $allowed, true)) {
        return false;
    }

    if ($name !== 'href' && $name !== 'src') {
        return true;
    }

    // browsers strip tabs and new lines from urls, so they cannot separate a scheme
    $value = \str_replace(["\t", "\n", "\r", "\0"], '', \trim($value));

    // relative urls are fine
    if (!\preg_match('/^[a-z][a-z0-9+.-]*:/i', $value)) {
        return true;
    }

    $lower = \strtolower($value);

    if (\str_starts_with($lower, 'javascript:') || \str_starts_with($lower, 'vbscript:')) {
        return false;
    }

    if ($tag === 'img' && $name === 'src' && \str_starts_with($lower, 'data:image/')) {
        return true;
    }

    return \preg_match('/^(https?|mailto|tel):/i', $value) === 1;
}

function handle_embedded_data_url_images(Config $config, string $html): array
{
    \libxml_use_internal_errors(true);
    // drop errors from earlier parsing, the check at the end reads the whole buffer
    \libxml_clear_errors();

    $dom = new \DOMDocument();

    if (!$dom->loadHTML($html)) {
        return [false, ['Could not load HTML']];
    }

    $imgAttrs = ['data-jaco-editable-image', 'data-jaco-editable-image-extended'];
    $imgSrcAttr = 'src';
    $imgFileAttr = 'data-jaco-editable-image-file';

    $images = $dom->getElementsByTagName('img');

    $handled = 0;
    $errors = [];

    foreach ($images as $el) {
        /** @var \DOMElement $el */

        $hasImgAttr = false;
        foreach ($imgAttrs as $imgAttr) {
            if ($el->hasAttribute($imgAttr)) {
                $hasImgAttr = true;
                break;
            }
        }

        if (!$hasImgAttr || !$el->hasAttribute($imgSrcAttr) || !$el->hasAttribute($imgFileAttr)) {
            continue;
        }

        $imgSrc = $el->getAttribute($imgSrcAttr);
        $imgFile = $el->getAttribute($imgFileAttr);

        if (($pos = \strpos($imgSrc, ',')) === false) {
            continue;
        }

        // normalize cosmetic issues (whitespace, umlauts), traversal is rejected loudly below
        $imgFile = sanitize\normalize_filename($imgFile);

        // only plain file names with an allowed extension may be written
        if (\str_contains($imgFile, DIRECTORY_SEPARATOR)
            || !filesystem\validate_filename($imgFile)
            || !filesystem\validate_extension($imgFile, $config->uploadExtensions)) {
            $errors[] = 'Invalid file name or extension: ' . $imgFile;
            continue;
        }

        $encodedData = \str_replace(' ', '+', \substr($imgSrc, $pos + 1));
        $decodedData = \base64_decode($encodedData, true);

        if ($decodedData === false) {
            $errors[] = 'Could not decode image data: ' . $imgFile;
            continue;
        }

        [$status, $newFile] = filesystem\write_file_without_overwrite($config, $config->uploadDir, $imgFile, $decodedData);

        if ($status === false) {
            $errors[] = 'Could not write file: ' . $imgFile;
            continue;
        }

        $el->setAttribute('src', filesystem\combine($config->uploadUrl, $newFile));
        $el->removeAttribute($imgFileAttr);
        $handled++;
    }

    if (\count($errors) > 0) {
        return [false, $errors];
    }

    if ($handled === 0) {
        return [true, $html];
    }

    $dom->formatOutput = true;
    $newHtml = $dom->saveHTML();

    if ($newHtml === false) {
        return [false, ['Could not save HTML']];
    }

    $errors = \libxml_get_errors();

    if (\count($errors) > 0) {
        \libxml_clear_errors();
        return [false, array_map(fn(\LibXMLError $e) => $e->message, $errors)];
    }

    return [true, $newHtml];
}

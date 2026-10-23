<?php declare(strict_types=1);

namespace jaco\core\html;

use jaco\core\config\Config;
use jaco\core\data\sanitize;
use jaco\core\filesystem;

function get_title(?string $html): string
{
    $title = '';

    if ($html === null || $html === '') {
        return $title;
    }

    $dom = new \DOMDocument();
    \libxml_use_internal_errors(true);
    // without a charset hint libxml assumes ISO-8859-1 and mangles non-ascii titles
    if ($dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html)) {
        $list = $dom->getElementsByTagName("title");
        $node = $list->item(0);
        if ($node instanceof \DOMElement) {
            $title = $node->textContent;
        }
    }
    \libxml_clear_errors();
    return $title;
}


function get_meta_content(?string $html, string $name): string
{
    if ($html === null || $html === '') {
        return '';
    }

    $dom = new \DOMDocument();
    \libxml_use_internal_errors(true);
    if ($dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html)) {
        foreach ($dom->getElementsByTagName('meta') as $node) {
            if (\strtolower($node->getAttribute('name')) !== \strtolower($name)) {
                continue;
            }
            \libxml_clear_errors();
            return $node->getAttribute('content');
        }
    }
    \libxml_clear_errors();
    return '';
}

function set_meta_content(string $html, string $name, string $content): string
{
    $quotedName = \preg_quote($name, '#');
    $pattern = '#<meta\b(?=[^>]*\bname\s*=\s*["\']' . $quotedName . '["\'])[^>]*>\s*#i';
    $stripped = \preg_replace($pattern, '', $html) ?? $html;

    if ($content === '') {
        return $stripped;
    }

    $tag = '<meta name="' . \htmlspecialchars($name, ENT_QUOTES) . '" content="'
        . \htmlspecialchars($content, ENT_QUOTES) . '">';
    $headEnd = \preg_match('#</head\s*>#i', $stripped, $matches, PREG_OFFSET_CAPTURE);
    if ($headEnd !== 1) {
        return $stripped;
    }

    $position = $matches[0][1];
    return \substr($stripped, 0, $position) . '  ' . $tag . "\n" . \substr($stripped, $position);
}

function get_description(?string $html): string
{
    return get_meta_content($html, 'description');
}

function set_description(string $html, string $description): string
{
    return set_meta_content($html, 'description', $description);
}

function get_robots(?string $html): string
{
    return get_meta_content($html, 'robots');
}

function set_robots(string $html, string $robots): string
{
    return set_meta_content($html, 'robots', $robots);
}

function set_title(string $html, string $title): string
{
    $replaced = \preg_replace('/<title>(.*?)<\/title>/', '<title>' . \htmlspecialchars($title) . '</title>', $html);
    if ($replaced === null) {
        return $html;
    }
    return $replaced;
}



/**
 * A site template is one root element. The marker is added to that root so a
 * later "apply to all" can find the element again.
 */
function template_with_marker(string $template, string $name): ?string
{
    $trimmed = \trim($template);
    if ($trimmed === '' || \preg_match('/\A<([a-zA-Z][a-zA-Z0-9:-]*)([^>]*)>/s', $trimmed, $match) !== 1) {
        return null;
    }

    $dom = new \DOMDocument();
    \libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML('<div id="jaco-template-root">' . $trimmed . '</div>');
    \libxml_clear_errors();
    if (!$loaded) {
        return null;
    }

    $wrapper = null;
    foreach ($dom->getElementsByTagName('div') as $candidate) {
        if ($candidate->getAttribute('id') === 'jaco-template-root') {
            $wrapper = $candidate;
            break;
        }
    }
    if ($wrapper === null) {
        return null;
    }

    $elements = 0;
    foreach ($wrapper->childNodes as $child) {
        if ($child instanceof \DOMElement) {
            $elements++;
        } elseif ($child->nodeType === XML_TEXT_NODE && \trim($child->textContent) !== '') {
            return null;
        }
    }
    if ($elements !== 1) {
        return null;
    }

    $attributes = $match[2];
    $selfClosing = \preg_match('#/\s*$#', $attributes) === 1;
    $attributes = \preg_replace(
        '/\s+data-jaco-template\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
        '',
        $attributes,
    ) ?? $attributes;
    if ($selfClosing) {
        $attributes = \preg_replace('#/\s*$#', '', $attributes) ?? $attributes;
    }
    $attributes = \rtrim($attributes);
    $marker = ' data-jaco-template="' . \htmlspecialchars($name, ENT_QUOTES) . '"';
    $opening = '<' . $match[1] . $attributes . $marker . ($selfClosing ? ' /' : '') . '>';

    return $opening . \substr($trimmed, \strlen($match[0]));
}

function count_template_elements(string $html, string $name): int
{
    $pattern = '#<[a-zA-Z][a-zA-Z0-9:-]*\b[^>]*\bdata-jaco-template\s*=\s*(["\'])' . \preg_quote($name, '#') . '\1[^>]*>#i';

    return \preg_match_all($pattern, $html) ?: 0;
}

/**
 * Replace every marked element in a document. Returns the changed document and
 * the number of replaced elements.
 *
 * @return array{0: string, 1: int}
 */
function replace_template_elements(string $html, string $name, string $template): array
{
    $pattern = '#<([a-zA-Z][a-zA-Z0-9:-]*)\b[^>]*\bdata-jaco-template\s*=\s*(["\'])' . \preg_quote($name, '#') . '\2[^>]*>#i';
    \preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

    $result = $html;
    $count = 0;
    $positions = $matches[0] ?? [];
    for ($i = \count($positions) - 1; $i >= 0; $i--) {
        $opening = $positions[$i][0];
        $start = $positions[$i][1];
        $tag = $matches[1][$i][0];
        $end = find_element_end($result, $tag, $start + \strlen($opening));
        if ($end === null) {
            continue;
        }
        $result = \substr($result, 0, $start) . $template . \substr($result, $end);
        $count++;
    }

    return [$result, $count];
}

function find_element_end(string $html, string $tag, int $offset): ?int
{
    if (\in_array(\strtolower($tag), ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'], true)) {
        return $offset;
    }

    $pattern = '#<(/?)' . \preg_quote($tag, '#') . '\b[^>]*>#i';
    $depth = 1;
    $position = $offset;
    while (\preg_match($pattern, $html, $match, PREG_OFFSET_CAPTURE, $position) === 1) {
        $full = $match[0][0];
        $start = $match[0][1];
        $closing = $match[1][0] === '/';
        if ($closing) {
            $depth--;
            if ($depth === 0) {
                return $start + \strlen($full);
            }
        } elseif (\preg_match('#/\s*>$#', $full) !== 1) {
            $depth++;
        }
        $position = $start + \strlen($full);
    }

    return null;
}
const ALLOWED_TAGS = [
    'a', 'abbr', 'article', 'aside', 'b', 'blockquote', 'br', 'button', 'caption', 'circle', 'cite', 'code', 'col', 'colgroup',
    'dd', 'del', 'details', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2',
    'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'i', 'iframe', 'img', 'ins', 'kbd', 'li', 'main', 'mark', 'nav', 'ol', 'p',
    'path', 'pre', 'q', 's', 'samp', 'section', 'small', 'span', 'strong', 'sub', 'summary', 'sup', 'svg',
    'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr', 'u', 'ul', 'var',
];

const REMOVE_TAGS = [
    'applet', 'base', 'embed', 'link', 'math', 'meta', 'noscript', 'object',
    'script', 'style', 'template',
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
        'data-jaco-editable-image-gallery',
        'data-jaco-content-list',
        'data-jaco-content-item',
        'data-jaco-fragment',
        'data-jaco-title',
        'data-jaco-pos',
    ],
    'a' => ['href', 'rel', 'target', 'title'],
    'button' => ['aria-expanded', 'aria-label', 'title', 'type'],
    'circle' => ['cx', 'cy', 'r'],
    'iframe' => ['allowfullscreen', 'frameborder', 'height', 'src', 'title', 'width'],
    'img' => ['alt', 'height', 'src', 'title', 'width'],
    'path' => ['d'],
    'svg' => ['aria-hidden', 'viewbox'],
    'td' => ['colspan', 'rowspan'],
    'th' => ['colspan', 'rowspan'],
];

function sanitize_html(string $html): string
{
    \libxml_use_internal_errors(true);

    $dom = new \DOMDocument();

    // libxml parses HTML 4: sectioning elements it does not know (header,
    // main, footer and friends) land in <head> when they appear before any
    // <body>, so a fragment starting with one would lose the whole block
    // including its content. Fragments therefore parse as explicit body
    // content; full documents pass through untouched.
    if (\preg_match('#<body[\s>]#i', $html) !== 1) {
        $html = '<body>' . $html . '</body>';
    }

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
        if (!is_allowed_attribute($tag, \strtolower($attr->nodeName), $attr->nodeValue ?? '')) {
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

        if ($childTag === 'iframe' && !$child->hasAttribute('src')) {
            // a player without a trusted source is dead weight, drop it
            $el->removeChild($child);
            continue;
        }

        if (!\in_array($childTag, ALLOWED_TAGS, true)) {
            // unwrap the element, keep its sanitized children
            while ($child->firstChild !== null) {
                $el->insertBefore($child->firstChild, $child);
            }
            $el->removeChild($child);
        }
    }
}

function is_allowed_attribute(string $tag, string $name, string $value): bool
{
    $allowed = \array_merge(ALLOWED_ATTRIBUTES['*'], ALLOWED_ATTRIBUTES[$tag] ?? []);

    if (!\in_array($name, $allowed, true)) {
        return false;
    }

    // embedded players are the only iframe use case: same-origin script
    // vectors stay out by restricting sources to the YouTube embeds
    if ($tag === 'iframe' && $name === 'src') {
        $value = \str_replace(["\t", "\n", "\r", "\0"], '', \trim($value));

        return \preg_match('#^https://(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/)#i', $value) === 1;
    }

    if ($name !== 'href' && $name !== 'src') {
        return true;
    }

    // browsers strip tabs and new lines from urls, so they cannot separate a scheme
    $value = \str_replace(["\t", "\n", "\r", "\0"], '', \trim($value));

    // protocol-relative urls inherit the page scheme and can point anywhere;
    // browsers also treat leading backslashes as slashes in web addresses
    if (\str_starts_with($value, '//') || \str_starts_with($value, '\\')) {
        return false;
    }

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

/**
 * Strips active content from an SVG upload before it reaches upload/: script
 * elements, foreign objects, event handler attributes and script urls are
 * removed, shapes and styling pass through unchanged. Malformed markup is
 * rejected instead of guessed. Returns [true, cleaned] or [false, errors].
 */
function sanitize_svg(string $bytes): array
{
    \libxml_use_internal_errors(true);

    $dom = new \DOMDocument();

    // no network for external entities, errors are collected, not printed
    if ($bytes === '' || !$dom->loadXML($bytes, LIBXML_NONET)) {
        \libxml_clear_errors();
        return [false, ['Invalid SVG file.']];
    }

    $root = $dom->documentElement;

    if (!$root instanceof \DOMElement || \strtolower($root->localName ?? '') !== 'svg') {
        \libxml_clear_errors();
        return [false, ['Invalid SVG file.']];
    }

    sanitize_svg_node($root);

    \libxml_clear_errors();

    $cleaned = $dom->saveXML();

    if ($cleaned === false) {
        return [false, ['Could not save SVG file.']];
    }

    return [true, $cleaned];
}

function sanitize_svg_node(\DOMElement $el): void
{
    foreach (\iterator_to_array($el->childNodes) as $child) {
        if (!$child instanceof \DOMElement) {
            continue;
        }

        $tag = \strtolower($child->localName ?? $child->nodeName);

        // script runs, foreignObject embeds arbitrary HTML: neither belongs
        // in an image file served from upload/
        if ($tag === 'script' || $tag === 'foreignobject') {
            $el->removeChild($child);
            continue;
        }

        sanitize_svg_node($child);
    }

    foreach (\iterator_to_array($el->attributes) as $attr) {
        $name = \strtolower($attr->nodeName);

        if (\str_starts_with($name, 'on')) {
            $el->removeAttribute($attr->nodeName);
            continue;
        }

        if (\in_array($name, ['href', 'xlink:href', 'src'], true)) {
            $value = \strtolower(\str_replace(["\t", "\n", "\r", "\0"], '', \trim((string) $attr->nodeValue)));

            if (\str_starts_with($value, 'javascript:')
                || \str_starts_with($value, 'vbscript:')
                || (\str_starts_with($value, 'data:') && !\str_starts_with($value, 'data:image/'))) {
                $el->removeAttribute($attr->nodeName);
            }
        }
    }
}

function handle_embedded_data_url_images(Config $config, string $html): array
{
    \libxml_use_internal_errors(true);
    // drop errors from earlier parsing, the check at the end reads the whole buffer
    \libxml_clear_errors();

    $dom = new \DOMDocument();

    if ($html === '' || !$dom->loadHTML($html)) {
        return [false, ['Could not load HTML']];
    }

    $imgAttrs = ['data-jaco-editable-image', 'data-jaco-editable-image-extended'];
    $imgSrcAttr = 'src';
    $imgFileAttr = 'data-jaco-editable-image-file';
    $galleryAttr = 'data-jaco-editable-image-gallery';
    $galleryFullAttr = 'data-jaco-gallery-full';

    // snapshot: gallery handling moves nodes, which a live list would not survive
    $images = \iterator_to_array($dom->getElementsByTagName('img'));

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
        $imgFile = sanitize\transliterate_filename($imgFile);

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

        // svg uploads are scrubbed of active content on both write paths
        if (\strtolower((string) \pathinfo($imgFile, PATHINFO_EXTENSION)) === 'svg') {
            [$svgOk, $svgCleaned] = sanitize_svg($decodedData);
            if ($svgOk !== true || !\is_string($svgCleaned)) {
                $errors[] = 'Invalid SVG file: ' . $imgFile;
                continue;
            }
            $decodedData = $svgCleaned;
        }

        [$status, $newFile] = filesystem\write_file_without_overwrite($config, $config->mediaDir, $imgFile, $decodedData);

        if ($status === false) {
            $errors[] = 'Could not write file: ' . $imgFile;
            continue;
        }

        $el->setAttribute('src', filesystem\combine($config->mediaUrl, $newFile));
        $el->removeAttribute($imgFileAttr);

        if ($el->hasAttribute($galleryAttr)) {
            $galleryError = write_gallery_full($dom, $config, $el, $imgFile, $galleryFullAttr);
            if ($galleryError !== null) {
                $errors[] = $galleryError;
                continue;
            }
        }

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

/**
 * Gallery images link their thumbnail to the full version: the client sends
 * the full data url in a temporary attribute, the server stores it as a
 * second file ("name-full.ext") and wraps the thumbnail in a link. An
 * existing parent link (e.g. an editable link) is never clobbered. Without
 * the temporary attribute there is nothing to link, the thumbnail stays
 * plain. Returns an error message or null on success.
 */
function write_gallery_full(\DOMDocument $dom, Config $config, \DOMElement $el, string $imgFile, string $galleryFullAttr): ?string
{
    if (!$el->hasAttribute($galleryFullAttr)) {
        return null;
    }

    $fullSrc = $el->getAttribute($galleryFullAttr);
    $el->removeAttribute($galleryFullAttr);

    // an explicit parent link wins over the gallery link, no second file then
    $parent = $el->parentNode;

    if ($parent instanceof \DOMElement && \strtolower($parent->nodeName) === 'a') {
        return null;
    }

    $fullFile = \pathinfo($imgFile, PATHINFO_FILENAME) . '-full.'
        . \pathinfo($imgFile, PATHINFO_EXTENSION);

    if (($pos = \strpos($fullSrc, ',')) === false) {
        return null;
    }

    $fullData = \base64_decode(\str_replace(' ', '+', \substr($fullSrc, $pos + 1)), true);

    if ($fullData === false) {
        return 'Could not decode image data: ' . $fullFile;
    }

    // svg uploads are scrubbed of active content on both write paths
    if (\strtolower((string) \pathinfo($fullFile, PATHINFO_EXTENSION)) === 'svg') {
        [$svgOk, $svgCleaned] = sanitize_svg($fullData);
        if ($svgOk !== true || !\is_string($svgCleaned)) {
            return 'Invalid SVG file: ' . $fullFile;
        }
        $fullData = $svgCleaned;
    }

    if (\str_contains($fullFile, DIRECTORY_SEPARATOR)
        || !filesystem\validate_filename($fullFile)
        || !filesystem\validate_extension($fullFile, $config->uploadExtensions)) {
        return 'Invalid file name or extension: ' . $fullFile;
    }

    [$status, $newFull] = filesystem\write_file_without_overwrite($config, $config->mediaDir, $fullFile, $fullData);

    if ($status === false) {
        return 'Could not write file: ' . $fullFile;
    }

    if ($parent instanceof \DOMElement) {
        $link = $dom->createElement('a');
        $link->setAttribute('href', filesystem\combine($config->mediaUrl, $newFull));
        $parent->replaceChild($link, $el);
        $link->appendChild($el);
    }

    return null;
}

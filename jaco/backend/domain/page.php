<?php declare(strict_types=1);

namespace jaco\domain\page;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\core\list;
use jaco\domain\Payload;

const SORT_PARAMETERS = ['created', 'file', 'title'];
const ROBOTS_PATTERN = '/^(index|noindex|follow|nofollow|noarchive)(,\s*(index|noindex|follow|nofollow|noarchive))*$/i';

final class Page
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
        public ?string $content,
    ) {}
}


function find_pages(Config $config, string $filteredBy, string $sortedBy): Payload
{
    $errors = data\validate(
        [
            'filteredBy' => $filteredBy,
            'sortedBy' => $sortedBy,
        ],
        [
            ['filteredBy', 'is_string'],
            ['sortedBy', 'is_string'],
            ['sortedBy', 'is_sort_parameter', 'fields' => SORT_PARAMETERS],
        ],
    );

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $files = find_public_pages($config);

    $sorted = list\parse_sort($sortedBy, SORT_PARAMETERS);
    $pages = create_list($files, $config->webDir);
    $pages = list\filter_list($pages, $filteredBy);
    $pages = list\sort_list($pages, $sorted);

    return Payload::success([
        'items' => $pages,
        'filter' => $filteredBy,
        'sort' => $sorted,
    ]);
}

/**
 * Every file of the published site.
 *
 * Only the application was excluded once, so the operator data (`jaco-data`: the
 * site templates and the backups) and the media directory were walked as pages -
 * the page list showed them, the overview counted them, and a site template got
 * applied to its own source and to every backup that carried a copy of the marker.
 *
 * @return array<filesystem\File>
 */
function find_public_pages(Config $config): array
{
    $excluded = [$config->appDir, $config->dataDir, $config->mediaDir];
    // a directory outside the web root would exclude everything: skip it instead
    $excluded = \array_values(\array_filter(
        $excluded,
        static fn(string $dir): bool => filesystem\is_inside_dir($dir, $config->webDir),
    ));

    return filesystem\find_files_rec($config->webDir, $config->pageExtensions, $excluded);
}

function find_page(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->webDir],
        ['has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $file);

    if ($html === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    return Payload::success([
        'file' => $file,
        'title' => html\get_title($html),
        'description' => html\get_description($html),
        'robots' => html\get_robots($html),
        'created' => filesystem\file_mtime($file, $config->webDir),
        'content' => $html,
    ]);
}

function delete_page(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->webDir],
        ['is_file_writable', 'dir' => $config->webDir],
        ['has_html_file_name'],
        ['has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $status = filesystem\backup_file($config->webDir, $file, $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\delete_file($config->webDir, $file);

    if (!$status) {
        return Payload::error([['file', 'Could not delete file.']]);
    }

    return Payload::success();
}

function copy_page(Config $config, string $file, string $toFile, string $withTitle): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'toFile' => $toFile,
            'withTitle' => $withTitle,
        ],
        [
            ['file', 'to_filename'],
            ['toFile', 'to_filename'],
            ['withTitle', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['toFile', 'is_required'],
        ['toFile', 'has_html_file_name'],
        ['toFile', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['toFile', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['toFile', 'is_file_present', 'dir' => $config->webDir, 'negate' => true],
        ['withTitle', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $data['file']);

    if ($html === null) {
        return Payload::error([['file', 'Could not read file content.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file($config, $config->webDir, $data['toFile'], $html);

    if ($status === false) {
        return Payload::error([['toFile', 'Could not write file content.']]);
    }

    $html = filesystem\read_file($config->webDir, $data['toFile']);

    if ($html === null) {
        return Payload::error([['toFile', 'Could not read new page.']]);
    }

    return Payload::success([
        'file' => $data['toFile'],
        'title' => html\get_title($html),
        'created' => filesystem\file_mtime($data['toFile'], $config->webDir),
        'content' => $html,
    ]);
}

function create_page(Config $config, string $file, string $fromTemplate, string $withTitle): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'fromTemplate' => $fromTemplate,
            'withTitle' => $withTitle,
        ],
        [
            ['fromTemplate', 'to_filename'],
            ['file', 'to_filename'],
            ['withTitle', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['file', 'is_file_present', 'dir' => $config->webDir, 'negate' => true],
        ['fromTemplate', 'is_required'],
        ['withTitle', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->templatePageDir, $data['fromTemplate']);

    if ($html === null) {
        return Payload::error([['fromTemplate', 'Could not read file.']]);
    }

    $html = html\set_title($html, $data['withTitle']);

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $html);

    if (!$status) {
        return Payload::error([['file', 'Could not write file.']]);
    }

    return Payload::success();
}

function update_page(
    Config $config,
    string $file,
    string $withContent,
    ?string $withTitle = null,
    ?string $withDescription = null,
    ?string $withRobots = null,
): Payload {
    $data = data\sanitize(
        [
            'file' => $file,
            'withContent' => $withContent,
        ],
        [
            ['file', 'to_string'],
            ['file', 'to_filename'],
            ['withContent', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['withContent', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($config, $data['withContent']);

    if ($status) {
        $unknownNames = html\data_attribute_unknown_names((string) $htmlOrErrors);

        if (\count($unknownNames) > 0) {
            return Payload::invalid(\array_map(
                static fn(string $name): array => ['withContent', 'unknown name: ' . $name],
                $unknownNames,
            ));
        }

        // the page manager owns the document: doctype and head stay as sent,
        // only the body is treated as content and sanitized
        $body = html\sanitize_html($htmlOrErrors);
        $contractErrors = \array_merge(
            html\data_attribute_errors($body),
            html\fragment_file_problems($config, html\repeater_fragments($body)),
        );

        if (\count($contractErrors) > 0) {
            return Payload::invalid(\array_map(
                static fn(string $problem): array => ['withContent', $problem],
                $contractErrors,
            ));
        }

        $data['withContent'] = html\replace_body($data['withContent'], $body);
    } else {
        return Payload::error([['withContent', \reset($htmlOrErrors)]]);
    }

    $metaErrors = validate_page_metadata($withTitle, $withDescription, $withRobots);
    if (\count($metaErrors) > 0) {
        return Payload::invalid($metaErrors);
    }
    $data['withContent'] = apply_page_metadata(
        $data['withContent'],
        $withTitle,
        $withDescription,
        $withRobots,
    );

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $data['withContent']);

    if (!$status) {
        return Payload::error([['file', 'Could not write content to file.']]);
    }

    return Payload::success();
}


/**
 * Validate optional metadata fields. Null means "leave as is"; an empty
 * string removes the tag.
 *
 * @return list<array{0: string, 1: string}>
 */
function validate_page_metadata(
    ?string $title,
    ?string $description,
    ?string $robots,
): array {
    $errors = [];

    if ($title !== null && \trim($title) === '') {
        $errors[] = ['pageTitle', 'Page title is required.'];
    }

    if ($description !== null && \strlen($description) > 320) {
        $errors[] = ['metaDescription', 'Meta description is too long.'];
    }

    if ($robots !== null && $robots !== '' && \preg_match(ROBOTS_PATTERN, $robots) !== 1) {
        $errors[] = ['robots', 'Invalid robots value.'];
    }

    return $errors;
}

/**
 * Apply already validated metadata to a document.
 */
function apply_page_metadata(
    string $html,
    ?string $title,
    ?string $description,
    ?string $robots,
): string {
    if ($title !== null) {
        $html = html\set_title($html, $title);
    }
    if ($description !== null) {
        $html = html\set_description($html, $description);
    }
    if ($robots !== null) {
        $html = html\set_robots($html, $robots);
    }

    return $html;
}

/**
 * Update only metadata. Unlike update_page(), this leaves the body untouched.
 */
function update_page_settings(
    Config $config,
    string $file,
    string $title,
    string $description,
    string $robots,
): Payload {
    $data = data\sanitize(
        ['file' => $file, 'title' => $title, 'description' => $description, 'robots' => $robots],
        [
            ['file', 'to_filename'],
            ['title', 'to_string'],
            ['description', 'to_string'],
            ['robots', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
    ]);

    if (\count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $html = filesystem\read_file($config->webDir, $data['file']);
    if ($html === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    $metaErrors = validate_page_metadata($data['title'], $data['description'], $data['robots']);
    if (\count($metaErrors) > 0) {
        return Payload::invalid($metaErrors);
    }
    $html = apply_page_metadata($html, $data['title'], $data['description'], $data['robots']);

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);
    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    if (!filesystem\write_file($config, $config->webDir, $data['file'], $html)) {
        return Payload::error([['file', 'Could not write content to file.']]);
    }

    return Payload::success([
        'file' => $data['file'],
        'title' => html\get_title($html),
        'description' => html\get_description($html),
        'robots' => html\get_robots($html),
        'created' => filesystem\file_mtime($data['file'], $config->webDir),
        'content' => $html,
    ]);
}

/**
 * Update only the body of a page. The inline editor sends content, never a document,
 * so the document around it - doctype and head - stays untouched.
 */
function update_page_body(Config $config, string $file, string $withBody): Payload
{
    $data = data\sanitize(
        [
            'file' => $file,
            'withBody' => $withBody,
        ],
        [
            ['file', 'to_string'],
            ['file', 'to_filename'],
            ['withBody', 'to_string'],
        ],
    );

    $errors = data\validate($data, [
        ['file', 'is_required'],
        ['file', 'is_file_present', 'dir' => $config->webDir],
        ['file', 'has_html_file_name'],
        ['file', 'has_file_extension', 'allowedExtensions' => $config->pageExtensions],
        ['file', 'is_file_in_dir', 'dir' => $config->appDirName, 'negate' => true],
        ['withBody', 'is_required'],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    [$status, $htmlOrErrors] = html\handle_embedded_data_url_images($config, $data['withBody']);

    if (!$status) {
        return Payload::error([['withBody', \reset($htmlOrErrors)]]);
    }

    $unknownNames = html\data_attribute_unknown_names((string) $htmlOrErrors);

    if (\count($unknownNames) > 0) {
        return Payload::invalid(\array_map(
            static fn(string $name): array => ['withBody', 'unknown name: ' . $name],
            $unknownNames,
        ));
    }

    // the client owns the content, the stored document keeps doctype and head
    $document = filesystem\read_file($config->webDir, $data['file']) ?? '';
    $body = html\sanitize_html($htmlOrErrors);
    $contractErrors = \array_merge(
        html\data_attribute_errors($body),
        html\fragment_file_problems($config, html\repeater_fragments($body)),
    );

    if (\count($contractErrors) > 0) {
        return Payload::invalid(\array_map(
            static fn(string $problem): array => ['withBody', $problem],
            $contractErrors,
        ));
    }

    $data['withBody'] = html\replace_body($document, $body);

    $status = filesystem\backup_file($config->webDir, $data['file'], $config->backupDir);

    if (!$status) {
        return Payload::error([['file', 'Could not backup file.']]);
    }

    $status = filesystem\write_file($config, $config->webDir, $data['file'], $data['withBody']);

    if (!$status) {
        return Payload::error([['file', 'Could not write content to file.']]);
    }

    return Payload::success();
}

/**
 * @param filesystem\File[] $files
 * @return Page[]
 */
function create_list(array $files, string $dir): array
{
    $pages = [];
    foreach ($files as $file) {
        $pages[] = create_one($dir, $file->file, $file->modified);
    }
    return $pages;
}

function create_one(string $dir, string $file, int $modified, ?string $content = null): Page
{
    $html = filesystem\read_file($dir, $file);

    return new Page(
        $file,
        html\get_title($html),
        $modified,
        $content,
    );
}

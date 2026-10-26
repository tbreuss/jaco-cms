<?php declare(strict_types=1);

namespace jaco\domain\template;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\domain\Payload;
use jaco\domain\page;

final class Template
{
    public function __construct(
        public string $file,
        public string $title,
        public int $created,
    ) {}
}

function find_page_templates(Config $config): Payload
{
    if (!filesystem\dir_exists($config->templatePageDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: $config->templatePageDir,
        allowedExtensions: $config->pageExtensions,
        withContent: true,
    );

    return Payload::success(
        array_map(fn(filesystem\File $file): Template => new Template(
            file: $file->file,
            title: html\get_title($file->content),
            created: $file->modified,
        ), $files),
    );
}

function find_content_templates(Config $config): Payload
{
    if (!filesystem\dir_exists($config->templateContentDir)) {
        return Payload::error([['', 'Dir does not exist.']]);
    }

    $files = filesystem\find_files(
        inDir: $config->templateContentDir,
        allowedExtensions: ['html'],
        withContent: true,
    );

    return Payload::success(
        array_map(function (filesystem\File $file): Template {
            // fragments carry no title, so the file name is used
            $title = html\get_title((string) $file->content);
            return new Template(
                file: $file->file,
                title: $title !== '' ? $title : $file->file,
                created: $file->modified,
            );
        }, $files),
    );
}

function find_content_template(Config $config, string $file): Payload
{
    $file = data\sanitize_value($file, [
        ['to_filename'],
    ]);

    $errors = data\validate_value($file, 'file', [
        ['is_required'],
        ['is_file_present', 'dir' => $config->templateContentDir],
        ['has_html_file_name'],
        ['has_file_extension', 'allowedExtensions' => ['html']],
    ]);

    if (count($errors) > 0) {
        return Payload::invalid($errors);
    }

    $content = filesystem\read_file($config->templateContentDir, $file);

    if ($content === null) {
        return Payload::error([['file', 'Could not read file.']]);
    }

    return Payload::success([
        'file' => $file,
        'created' => filesystem\file_mtime($file, $config->templateContentDir),
        'content' => $content,
    ]);
}

function site_template_name(string $file): string
{
    return \pathinfo($file, PATHINFO_FILENAME);
}

function validate_site_template_file(string $file): array
{
    $file = data\sanitize_value($file, [['to_filename']]);
    if ($file === '' || \pathinfo($file, PATHINFO_EXTENSION) !== 'html' || \basename($file) !== $file) {
        return [null, [['file', 'Invalid template file.']]];
    }

    return [$file, []];
}

function find_site_templates(Config $config): Payload
{
    if (!\is_dir($config->templateSiteDir)) {
        return Payload::success([]);
    }

    $files = filesystem\find_files($config->templateSiteDir, ['html']);
    $items = [];
    foreach ($files as $file) {
        $items[] = [
            'file' => $file->file,
            'name' => site_template_name($file->file),
            'created' => $file->modified,
        ];
    }

    return Payload::success($items);
}

function find_site_template(Config $config, string $file): Payload
{
    [$file, $errors] = validate_site_template_file($file);
    if ($file === null) {
        return Payload::invalid($errors);
    }
    if (!\is_file($config->templateSiteDir . '/' . $file)) {
        return Payload::invalid([['file', 'Template does not exist.']]);
    }

    $content = filesystem\read_file($config->templateSiteDir, $file);
    if ($content === null) {
        return Payload::error([['file', 'Could not read template.']]);
    }

    return Payload::success([
        'file' => $file,
        'name' => site_template_name($file),
        'created' => filesystem\file_mtime($file, $config->templateSiteDir),
        'content' => $content,
    ]);
}

/**
 * @return array{files: list<array{file: string, elements: int}>, elements: int}
 */
function preview_site_template(Config $config, string $file): array
{
    $files = page\find_public_pages($config);
    $matches = [];
    $elements = 0;
    foreach ($files as $page) {
        $html = filesystem\read_file($config->webDir, $page->file);
        if ($html === null) {
            continue;
        }
        $count = html\count_template_elements($html, $file);
        if ($count > 0) {
            $matches[] = ['file' => $page->file, 'elements' => $count];
            $elements += $count;
        }
    }

    return ['files' => $matches, 'elements' => $elements];
}

/**
 * Apply one template to every public HTML page.
 *
 * Every page is backed up before it is written, like a page edit: applying
 * rewrites the structure of the whole site at once, and it used to be the one
 * operation without a way back. A page whose backup fails is skipped instead of
 * overwritten - it shows up in `failed` and stays as it was.
 *
 * @return array{files: int, elements: int, failed: list<string>}
 */
function apply_site_template(Config $config, string $file): array
{
    $template = find_site_template($config, $file);
    if (!$template->isSuccess) {
        return ['files' => 0, 'elements' => 0, 'failed' => []];
    }

    $withMarker = html\template_with_marker($template->data['content'], $file);
    if ($withMarker === null) {
        return ['files' => 0, 'elements' => 0, 'failed' => []];
    }

    $files = page\find_public_pages($config);
    $changedFiles = 0;
    $changedElements = 0;
    $failed = [];

    foreach ($files as $page) {
        $html = filesystem\read_file($config->webDir, $page->file);
        if ($html === null) {
            $failed[] = $page->file;
            continue;
        }
        [$updated, $count] = html\replace_template_elements($html, $file, $withMarker);
        if ($count === 0) {
            continue;
        }
        // backup first: without it the rewrite of every marked element in every
        // page would be the only change in Jaco that cannot be undone
        if (!filesystem\backup_file($config->webDir, $page->file, $config->backupDir)) {
            $failed[] = $page->file;
            continue;
        }
        if (!filesystem\write_file_atomic($config, $config->webDir, $page->file, $updated)) {
            $failed[] = $page->file;
            continue;
        }
        $changedFiles++;
        $changedElements += $count;
    }

    return ['files' => $changedFiles, 'elements' => $changedElements, 'failed' => $failed];
}

/**
 * Write the template and nothing else. Applying is its own call since the two
 * need different rights: an admin owns the markup, an editor may roll it out.
 */
function update_site_template(Config $config, string $file, string $content): Payload
{
    [$file, $errors] = validate_site_template_file($file);
    if ($file === null) {
        return Payload::invalid($errors);
    }
    if (html\template_with_marker($content, $file) === null) {
        return Payload::invalid([['content', 'Template must contain exactly one root element.']]);
    }

    $dir = $config->templateSiteDir;
    if (!\is_dir($dir) && !filesystem\make_dir_rec($config, $config->webDir, \substr($config->templateSiteDir, \strlen($config->webDir) + 1))) {
        return Payload::error([['file', 'Could not create template directory.']]);
    }
    if (!filesystem\write_file_atomic($config, $dir, $file, $content)) {
        return Payload::error([['file', 'Could not write template.']]);
    }

    return Payload::success([
        'file' => $file,
        'name' => site_template_name($file),
        'created' => filesystem\file_mtime($file, $dir),
        'content' => $content,
    ]);
}

/**
 * Write the template to every page that carries its marker. The save route does
 * not do this any more: applying is the step an editor may take without being
 * allowed to change the markup.
 */
function apply_site_template_to_all(Config $config, string $file): Payload
{
    [$file, $errors] = validate_site_template_file($file);
    if ($file === null) {
        return Payload::invalid($errors);
    }

    $template = find_site_template($config, $file);
    if (!$template->isSuccess) {
        return Payload::error([['file', 'Could not read template.']]);
    }

    $applied = apply_site_template($config, $file);

    return Payload::success([
        'file' => $file,
        'name' => site_template_name($file),
        'appliedFiles' => $applied['files'],
        'appliedElements' => $applied['elements'],
        'failed' => $applied['failed'],
    ]);
}

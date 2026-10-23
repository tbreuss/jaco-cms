<?php declare(strict_types=1);

namespace jaco\domain\template;

use jaco\core\config\Config;
use jaco\core\data;
use jaco\core\filesystem;
use jaco\core\html;
use jaco\domain\Payload;

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
    if (!\is_dir($config->siteTemplateDir)) {
        return Payload::success([]);
    }

    $files = filesystem\find_files($config->siteTemplateDir, ['html']);
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
    if (!\is_file($config->siteTemplateDir . '/' . $file)) {
        return Payload::invalid([['file', 'Template does not exist.']]);
    }

    $content = filesystem\read_file($config->siteTemplateDir, $file);
    if ($content === null) {
        return Payload::error([['file', 'Could not read template.']]);
    }

    return Payload::success([
        'file' => $file,
        'name' => site_template_name($file),
        'created' => filesystem\file_mtime($file, $config->siteTemplateDir),
        'content' => $content,
    ]);
}

/**
 * @return array{files: list<array{file: string, elements: int}>, elements: int}
 */
function preview_site_template(Config $config, string $name): array
{
    $files = filesystem\find_files_rec($config->webDir, $config->pageExtensions, [$config->appDir]);
    $matches = [];
    $elements = 0;
    foreach ($files as $file) {
        $html = filesystem\read_file($config->webDir, $file->file);
        if ($html === null) {
            continue;
        }
        $count = html\count_template_elements($html, $name);
        if ($count > 0) {
            $matches[] = ['file' => $file->file, 'elements' => $count];
            $elements += $count;
        }
    }

    return ['files' => $matches, 'elements' => $elements];
}

/**
 * Apply one template to every public HTML page. This deliberately writes
 * without a backup; the caller asked for a "no backup" operation.
 *
 * @return array{files: int, elements: int, failed: list<string>}
 */
function apply_site_template(Config $config, string $name): array
{
    $template = find_site_template($config, $name . '.html');
    if (!$template->isSuccess) {
        return ['files' => 0, 'elements' => 0, 'failed' => []];
    }

    $withMarker = html\template_with_marker($template->data['content'], $name);
    if ($withMarker === null) {
        return ['files' => 0, 'elements' => 0, 'failed' => []];
    }

    $files = filesystem\find_files_rec($config->webDir, $config->pageExtensions, [$config->appDir]);
    $changedFiles = 0;
    $changedElements = 0;
    $failed = [];

    foreach ($files as $file) {
        $html = filesystem\read_file($config->webDir, $file->file);
        if ($html === null) {
            $failed[] = $file->file;
            continue;
        }
        [$updated, $count] = html\replace_template_elements($html, $name, $withMarker);
        if ($count === 0) {
            continue;
        }
        if (!filesystem\write_file_atomic($config, $config->webDir, $file->file, $updated)) {
            $failed[] = $file->file;
            continue;
        }
        $changedFiles++;
        $changedElements += $count;
    }

    return ['files' => $changedFiles, 'elements' => $changedElements, 'failed' => $failed];
}

function update_site_template(Config $config, string $file, string $content, bool $applyToAll): Payload
{
    [$file, $errors] = validate_site_template_file($file);
    if ($file === null) {
        return Payload::invalid($errors);
    }
    if (html\template_with_marker($content, site_template_name($file)) === null) {
        return Payload::invalid([['content', 'Template must contain exactly one root element.']]);
    }

    $dir = $config->siteTemplateDir;
    if (!\is_dir($dir) && !filesystem\make_dir_rec($config, $config->webDir, \substr($config->siteTemplateDir, \strlen($config->webDir) + 1))) {
        return Payload::error([['file', 'Could not create template directory.']]);
    }
    if (!filesystem\write_file_atomic($config, $dir, $file, $content)) {
        return Payload::error([['file', 'Could not write template.']]);
    }

    $applied = ['files' => 0, 'elements' => 0, 'failed' => []];
    if ($applyToAll) {
        $applied = apply_site_template($config, site_template_name($file));
    }

    return Payload::success([
        'file' => $file,
        'name' => site_template_name($file),
        'created' => filesystem\file_mtime($file, $dir),
        'content' => $content,
        'appliedFiles' => $applied['files'],
        'appliedElements' => $applied['elements'],
        'failed' => $applied['failed'],
    ]);
}

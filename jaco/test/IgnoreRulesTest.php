<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * A tracked file and the rule that ignores its neighbours have to agree.
 *
 * The template directories ignored everything but lifted the rule for any name
 * starting with "sample_". The intention was "the shipped samples are tracked",
 * but the rule also opened the door for `sample_event.html` written in the
 * admin, which would then be committed by accident.
 *
 * Every file that git tracks has to survive `git check-ignore`; every name that
 * is meant to stay on the server must be ignored.
 */
#[TestDox('Ignore rules under jaco-data match the tracked files')]
final class IgnoreRulesTest extends TestCase
{
    public function testEveryTrackedFileCanBeAddedAgain(): void
    {
        // A tracked file stays writable in git even when a rule matches it - the
        // index wins. The danger is the reverse: after `git rm --cached` the rule
        // keeps it out for good. So the question is not "is it ignored" but "would
        // git take it again". `git add --dry-run` answers that, `git check-ignore`
        // does not: it also reports negations as a hit.
        $tracked = $this->git('ls-files');

        $this->assertNotNull($tracked, 'git lists the tracked files');
        $this->assertNotSame([], $tracked, 'the scan sees the tracked files');

        $refused = [];
        foreach ($tracked as $file) {
            if ($this->git('add', '--dry-run', '--', $file) === null) {
                $refused[] = $file;
            }
        }

        $this->assertSame(
            [],
            $refused,
            "a rule keeps git from adding a file that is tracked:\n  " . \implode("\n  ", $refused),
        );
    }

    public function testTheDenyFilesAndPlaceholdersShipWithTheRepository(): void
    {
        // without these a fresh clone has no directory to write into, and no
        // protection file on the server
        foreach ([
            'jaco-data/backups/.gitignore',
            'jaco-data/runtime/.gitignore',
            'jaco-data/templates/content/.gitignore',
            'jaco-data/templates/page/.gitignore',
            'jaco-data/templates/site/.gitignore',
            'media/.htaccess',
        ] as $file) {
            $this->assertNotNull(
                $this->git('add', '--dry-run', '--', $file),
                $file . ' is part of the repository and must stay addable',
            );
        }
    }

    public function testNewTemplateFilesStayOnTheServer(): void
    {
        // a name the admin would produce: not a shipped sample, so it must be ignored
        foreach (['content', 'page', 'site'] as $kind) {
            $path = 'jaco-data/templates/' . $kind . '/sample_new_' . $kind . '.html';
            $absolute = \dirname(__DIR__, 2) . '/' . $path;
            \file_put_contents($absolute, '<div>probe</div>');

            try {
                $this->assertNull(
                    $this->git('add', '--dry-run', '--', $path),
                    $path . ' would be committed although it was written on the server',
                );
            } finally {
                \unlink($absolute);
            }
        }
    }

    public function testFilesWrittenOnTheServerStayOut(): void
    {
        // each of these is created by the running application, never by a developer
        foreach ([
            'jaco-data/backups/20260101_000000_aaaa_index.html',
            'jaco-data/runtime/log/error.log',
        ] as $path) {
            $absolute = \dirname(__DIR__, 2) . '/' . $path;
            @\mkdir(\dirname($absolute), 0755, true);
            \file_put_contents($absolute, 'probe');

            try {
                $this->assertNull($this->git('add', '--dry-run', '--', $path), $path . ' would be committed');
            } finally {
                \unlink($absolute);
            }
        }
    }

    /**
     * Output lines of a git command, or null when the command reports "no match"
     * (exit code 1), which is what check-ignore uses.
     *
     * @return list<string>|null
     */
    private function git(string ...$arguments): ?array
    {
        $command = 'git -C ' . \escapeshellarg(\dirname(__DIR__, 2))
            . ' ' . \implode(' ', \array_map(\escapeshellarg(...), $arguments)) . ' 2>/dev/null';

        \exec($command, $output, $code);

        if ($code !== 0) {
            return null;
        }

        return $output;
    }
}

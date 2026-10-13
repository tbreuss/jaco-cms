<?php declare(strict_types=1);

/**
 * Fails the run when the coverage report of phpunit drops below the given
 * percentages. PHPUnit 11 no longer ships minimum coverage options, so the
 * gate lives here.
 *
 * The class ratio is deliberately not part of the gate: the marker exceptions
 * in core/error consist of an empty class body and are counted as uncovered
 * without a single line of untested code.
 *
 * Usage: php test/coverage-gate.php <clover.xml>
 */

const MIN_LINES = 80.0;

const MIN_METHODS = 80.0;

/** @var array<string, array{0: string, 1: string, 2: float}> $metrics total key, covered key, minimum */
const METRICS = [
    'lines' => ['statements', 'coveredstatements', MIN_LINES],
    'methods' => ['methods', 'coveredmethods', MIN_METHODS],
];

$report = $argv[1] ?? __DIR__ . '/runtime/coverage.xml';

if ($report === '' || !is_file($report)) {
    fwrite(STDERR, 'Coverage report not found: ' . $report . PHP_EOL);
    fwrite(STDERR, 'phpunit needs a coverage driver for this, run it with pcov or XDEBUG_MODE=coverage' . PHP_EOL);
    exit(1);
}

$document = new DOMDocument();
if (!$document->load($report)) {
    fwrite(STDERR, 'Coverage report is no readable xml: ' . $report . PHP_EOL);
    exit(1);
}

$nodes = (new DOMXPath($document))->query('/coverage/project/metrics');
$project = $nodes === false ? null : $nodes->item(0);
if (!$project instanceof DOMElement) {
    fwrite(STDERR, 'Coverage report has no project metrics: ' . $report . PHP_EOL);
    exit(1);
}

$failed = false;

foreach (METRICS as $name => [$totalKey, $coveredKey, $minimum]) {
    $total = (int) $project->getAttribute($totalKey);
    $covered = (int) $project->getAttribute($coveredKey);
    $percent = $total > 0 ? $covered / $total * 100 : 100.0;

    // compare the rounded value: a result that only looks below the limit
    // because of the two decimals of the ratio must not fail the build
    $ok = round($percent, 2) >= $minimum;

    printf(
        '%-8s %6.2f %% (%d/%d), minimum %6.2f %%  %s' . PHP_EOL,
        $name,
        $percent,
        $covered,
        $total,
        $minimum,
        $ok ? 'ok' : 'FAILED',
    );

    $failed = $failed || !$ok;
}

exit($failed ? 1 : 0);

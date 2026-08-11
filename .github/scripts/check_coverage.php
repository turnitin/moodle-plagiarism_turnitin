<?php
// phpcs:ignoreFile -- Standalone CLI script; not a Moodle plugin file.
/**
 * Parses a PHPUnit Clover XML coverage report and fails if total line coverage
 * is below the given threshold.
 *
 * Usage: php check_coverage.php <clover.xml> <threshold_percent>
 *
 * Exit codes: 0 = pass, 1 = below threshold or bad input.
 */

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php check_coverage.php <clover.xml> <threshold>\n");
    exit(1);
}

$cloverfile = $argv[1];
$threshold  = (float) $argv[2];

if (!file_exists($cloverfile)) {
    fwrite(STDERR, "ERROR: Coverage file not found: {$cloverfile}\n");
    exit(1);
}

$xml     = simplexml_load_file($cloverfile);
$metrics = $xml->project->metrics;
$covered = (int) $metrics['coveredstatements'];
$total   = (int) $metrics['statements'];

if ($total === 0) {
    fwrite(STDERR, "ERROR: Coverage report has no statements — PCOV may not be active.\n");
    exit(1);
}

$pct = round($covered / $total * 100, 2);
echo "Coverage: {$pct}% ({$covered}/{$total} lines)\n";

if ($pct < $threshold) {
    fwrite(STDERR, "FAIL: {$pct}% is below the {$threshold}% threshold.\n");
    exit(1);
}

echo "PASS: coverage meets the {$threshold}% threshold.\n";

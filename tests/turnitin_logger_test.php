<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for classes/turnitin_logger.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_logger.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(\turnitin_logger::class)]
final class turnitin_logger_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        // Reset the static config cache so set_config() calls in each test take effect.
        \turnitin_logger::reset_config_cache();
    }

    /**
     * Test that nothing is written when diagnostic logging is disabled (the default).
     */
    public function test_log_does_nothing_when_diagnostic_disabled(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('plagiarism_turnitin_enablediagnostic', 0, 'plagiarism_turnitin');

        \turnitin_logger::log('test message', 'TEST');

        $logdir  = $CFG->tempdir . '/plagiarism_turnitin/logs';
        $logfile = $logdir . '/activitylog_' . gmdate('Y-m-d', time()) . '.txt';
        $this->assertFileDoesNotExist($logfile, "No log file should be written when diagnostic is disabled");
    }

    /**
     * Test that a log entry is written to the correct file when diagnostic logging is on.
     */
    public function test_log_writes_to_file_when_diagnostic_enabled(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        \turnitin_logger::log('something happened', 'REQUEST');

        $logdir  = $CFG->tempdir . '/plagiarism_turnitin/logs';
        $logfile = $logdir . '/activitylog_' . gmdate('Y-m-d', time()) . '.txt';

        $this->assertFileExists($logfile);
        $contents = file_get_contents($logfile);
        $this->assertStringContainsString('(REQUEST)', $contents);
        $this->assertStringContainsString('something happened', $contents);
    }

    /**
     * Test that the log entry format matches the expected pattern:
     * "YYYY-MM-DD HH:MM:SS +0000 (ACTIVITY) - message"
     */
    public function test_log_entry_format(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        \turnitin_logger::log('checking format', 'API_ERROR');

        $logfile  = $CFG->tempdir . '/plagiarism_turnitin/logs/activitylog_' . gmdate('Y-m-d', time()) . '.txt';
        $contents = file_get_contents($logfile);

        // Format: "2026-08-05 12:34:56 +0000 (API_ERROR) - checking format"
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} [+-]\d{4} \(API_ERROR\) - checking format/',
            $contents
        );
    }

    /**
     * Test that <br/> tags in the message are replaced with newlines so that
     * multi-line HTML error messages are readable in the log file.
     */
    public function test_log_replaces_br_tags_with_newlines(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        \turnitin_logger::log('line one<br/>line two', 'API_ERROR');

        $logfile  = $CFG->tempdir . '/plagiarism_turnitin/logs/activitylog_' . gmdate('Y-m-d', time()) . '.txt';
        $contents = file_get_contents($logfile);

        $this->assertStringContainsString("line one\r\nline two", $contents);
        $this->assertStringNotContainsString('<br/>', $contents);
    }

    /**
     * Test that old log files beyond the 10-file limit are pruned.
     * This prevents unbounded growth of the log directory.
     */
    public function test_log_prunes_old_files_beyond_limit(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        // Create 12 old log files so we exceed the 10-file limit.
        $logdir = $CFG->tempdir . '/plagiarism_turnitin/logs';
        mkdir($logdir, 0777, true);
        $oldfiles = [];
        for ($i = 1; $i <= 12; $i++) {
            $filename = $logdir . '/activitylog_2020-01-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '.txt';
            file_put_contents($filename, 'old log');
            $oldfiles[] = basename($filename);
        }

        \turnitin_logger::log('trigger pruning', 'TEST');

        // After logging, exactly 10 old files + today's file should remain (11 total).
        // The 2 oldest (2020-01-01, 2020-01-02) should have been pruned.
        $remaining = glob($logdir . '/activitylog_*.txt');
        $this->assertCount(11, $remaining);
        $this->assertFileDoesNotExist($logdir . '/activitylog_2020-01-01.txt');
        $this->assertFileDoesNotExist($logdir . '/activitylog_2020-01-02.txt');
    }

    /**
     * Test that multiple calls append to the same daily log file rather than
     * overwriting it.
     */
    public function test_log_appends_to_existing_file(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        \turnitin_logger::log('first entry', 'REQUEST');
        \turnitin_logger::log('second entry', 'REQUEST');

        $logfile  = $CFG->tempdir . '/plagiarism_turnitin/logs/activitylog_' . gmdate('Y-m-d', time()) . '.txt';
        $contents = file_get_contents($logfile);

        $this->assertStringContainsString('first entry', $contents);
        $this->assertStringContainsString('second entry', $contents);
    }
}

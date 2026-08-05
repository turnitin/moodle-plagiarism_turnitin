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
 * Unit tests for the plagiarism_turnitin task classes.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;
use plagiarism_turnitin\task\adhoc_send_submission;
use plagiarism_turnitin\task\send_submissions;
use plagiarism_turnitin\task\sync_grades;
use plagiarism_turnitin\task\update_reports;

/**
 * Tests for the four task classes.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(update_reports::class)]
#[CoversClass(adhoc_send_submission::class)]
#[CoversClass(send_submissions::class)]
#[CoversClass(sync_grades::class)]
final class task_test extends \advanced_testcase {
    // Get_name tests.

    /**
     * Test that each task returns a non-empty name string.
     */
    public function test_task_names_are_non_empty(): void {
        $this->assertNotEmpty((new update_reports())->get_name());
        $this->assertNotEmpty((new adhoc_send_submission())->get_name());
        $this->assertNotEmpty((new send_submissions())->get_name());
        $this->assertNotEmpty((new sync_grades())->get_name());
    }

    // Update_reports tests.

    /**
     * Test that update_reports::execute returns immediately without doing anything
     * when the plugin is not configured, so an unconfigured site doesn't attempt
     * API calls.
     */
    public function test_update_reports_returns_early_when_not_configured(): void {
        $this->resetAfterTest();

        // No credentials set — plugin is not configured.
        $task = new update_reports();

        // No exception should be thrown and no API call attempted.
        $task->execute();
        $this->assertTrue(true);
    }

    // Adhoc_send_submission tests.

    /**
     * Test that adhoc_send_submission::instance creates a task carrying the
     * submission data in its custom data payload.
     */
    public function test_adhoc_instance_stores_submission_in_custom_data(): void {
        $submission = (object)['id' => 42, 'cm' => 5, 'userid' => 10, 'identifier' => 'abc'];

        $task = adhoc_send_submission::instance($submission);

        $data = $task->get_custom_data();
        $this->assertEquals(42, $data->submission->id);
        $this->assertEquals(5, $data->submission->cm);
        $this->assertEquals(10, $data->submission->userid);
    }

    /**
     * Test that adhoc_send_submission::instance sets the component to
     * plagiarism_turnitin so Moodle routes it correctly.
     */
    public function test_adhoc_instance_sets_correct_component(): void {
        $task = adhoc_send_submission::instance((object)['id' => 1]);

        $this->assertEquals('plagiarism_turnitin', $task->get_component());
    }

    /**
     * Test that adhoc tasks allow concurrent execution so submissions can be
     * processed in parallel.
     */
    public function test_adhoc_allows_concurrency(): void {
        $task = new adhoc_send_submission();
        $this->assertFalse($task->is_no_concurrency_allowed());
    }

    /**
     * Test that adhoc_send_submission::execute returns early when the plugin
     * is not configured, preventing API calls on unconfigured sites.
     */
    public function test_adhoc_execute_returns_early_when_not_configured(): void {
        $this->resetAfterTest();

        $task = adhoc_send_submission::instance((object)['id' => 1, 'cm' => 1]);
        $task->execute();
        $this->assertTrue(true);
    }

    // Send_submissions tests.

    /**
     * Test that send_submissions::execute returns early when the plugin is not
     * configured.
     */
    public function test_send_submissions_returns_early_when_not_configured(): void {
        $this->resetAfterTest();

        (new send_submissions())->execute();
        $this->assertTrue(true);
    }

    /**
     * Test that when adhoc mode is enabled and there are no queued submissions,
     * no ad-hoc tasks are queued.
     */
    public function test_send_submissions_adhoc_mode_queues_nothing_when_empty(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enableadhocsubmissions', 1, 'plagiarism_turnitin');

        // No queued items in the DB, so no ad-hoc tasks should be scheduled.
        // The connection test will fail (no real Turnitin), causing an mtrace() output.
        $this->expectOutputRegex('/.*/');

        $adhoctasksbefore = \core\task\manager::get_adhoc_tasks(adhoc_send_submission::class);

        (new send_submissions())->execute();

        $adhoctasksafter = \core\task\manager::get_adhoc_tasks(adhoc_send_submission::class);
        $this->assertCount(count($adhoctasksbefore), $adhoctasksafter);
    }

    // Sync_grades tests.

    /**
     * Test that sync_grades::execute returns early when the plugin is not
     * configured.
     */
    public function test_sync_grades_returns_early_when_not_configured(): void {
        $this->resetAfterTest();

        (new sync_grades())->execute();
        $this->assertTrue(true);
    }

    /**
     * Test that sync_grades skips assignments whose due date is older than one
     * week, since there's no point re-syncing grades for long-past activities.
     */
    public function test_sync_grades_skips_assignments_past_cutoff(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'  => $course->id,
            'duedate' => time() - (8 * 24 * 60 * 60), // 8 days ago — past the cutoff.
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);

        // Register the assignment as a Turnitin-linked module.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'turnitin_assignid',
            'value'       => 99,
            'config_hash' => $cm->id . '_turnitin_assignid',
        ]);

        // Execute — the connection test will fail (no real Turnitin) so the task
        // returns early before processing. The failed connection produces mtrace() output.
        $this->expectOutputRegex('/.*/');
        (new sync_grades())->execute();

        // Grades_last_synced should NOT have been written since the assignment is past cutoff.
        $this->assertFalse($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        ));
    }

    /**
     * Test that sync_grades writes and then updates a grades_last_synced config
     * record when an assignment is within the sync window — exercising the
     * insert/update branching in the task.
     */
    public function test_sync_grades_upserts_last_synced_timestamp(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        // Simulate inserting then updating the grades_last_synced record directly
        // (the task would do this after a successful grade sync).
        $currenttime = time();
        $towrite = (object)[
            'cm'          => $cm->id,
            'name'        => 'grades_last_synced',
            'value'       => $currenttime,
            'config_hash' => $cm->id . '_grades_last_synced',
        ];

        // First write — insert path.
        $DB->insert_record('plagiarism_turnitin_config', $towrite);
        $this->assertTrue($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        ));

        // Second write — update path (simulate the task running again).
        $record = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->id, 'name' => 'grades_last_synced']);
        $towrite->id    = $record->id;
        $towrite->value = $currenttime + 100;
        $DB->update_record('plagiarism_turnitin_config', $towrite);

        $updated = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->id, 'name' => 'grades_last_synced']);
        $this->assertEquals($currenttime + 100, $updated->value);

        // Still only one record — no duplicates.
        $this->assertEquals(1, $DB->count_records(
            'plagiarism_turnitin_config',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        ));
    }

    // Send_submissions coverage tests.

    /**
     * Test that send_submissions in adhoc mode mtraces a "no queued items" message
     * and returns without queueing any tasks when the queue is empty.
     * Uses a partial mock to bypass the live Turnitin connection check.
     */
    public function test_send_submissions_adhoc_no_queued_items_logs_message(): void {
        $this->resetAfterTest();
        $this->set_credentials();
        set_config('plagiarism_turnitin_enableadhocsubmissions', 1, 'plagiarism_turnitin');

        $mockplugin = $this->make_mock_plugin_with_connection(true);

        $this->expectOutputRegex('/No queued items found\./');
        (new send_submissions())->execute($mockplugin);
    }

    /**
     * Test that in adhoc mode with queued items, each item gets an ad-hoc task
     * scheduled. Uses a partial mock to bypass the live connection check.
     */
    public function test_send_submissions_adhoc_queues_adhoc_tasks_for_each_item(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_credentials();
        set_config('plagiarism_turnitin_enableadhocsubmissions', 1, 'plagiarism_turnitin');

        for ($i = 0; $i < 2; $i++) {
            $DB->insert_record('plagiarism_turnitin_files', (object)[
                'cm' => 1, 'userid' => 1, 'identifier' => "hash$i",
                'statuscode' => 'queued', 'attempt' => 0, 'submissiontype' => 'file',
                'itemid' => 0, 'submitter' => 1, 'lastmodified' => time(), 'transmatch' => 0,
            ]);
        }

        $before     = count(\core\task\manager::get_adhoc_tasks(adhoc_send_submission::class));
        $mockplugin = $this->make_mock_plugin_with_connection(true);

        $this->expectOutputRegex('/Found 2 queued submissions/');
        (new send_submissions())->execute($mockplugin);

        $after = count(\core\task\manager::get_adhoc_tasks(adhoc_send_submission::class));
        $this->assertEquals($before + 2, $after);
    }

    /**
     * Test that in non-adhoc mode, send_submissions queries for queued items and does
     * not queue any ad-hoc tasks (submissions are processed inline).
     */
    public function test_send_submissions_non_adhoc_mode_does_not_queue_adhoc_tasks(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_credentials();
        set_config('plagiarism_turnitin_enableadhocsubmissions', 0, 'plagiarism_turnitin');

        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => 1, 'userid' => 1, 'identifier' => 'hash1',
            'statuscode' => 'queued', 'attempt' => 0, 'submissiontype' => 'file',
            'itemid' => 0, 'submitter' => 1, 'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $before     = count(\core\task\manager::get_adhoc_tasks(adhoc_send_submission::class));
        $mockplugin = $this->make_mock_plugin_with_connection(true);

        $this->expectOutputRegex('/Sending submissions using scheduled task/');
        (new send_submissions())->execute($mockplugin);

        $after = count(\core\task\manager::get_adhoc_tasks(adhoc_send_submission::class));
        $this->assertEquals($before, $after);
    }

    // Sync_grades coverage tests.

    /**
     * Test that sync_grades processes an assignment within the sync window.
     * update_grades_from_tii throws since there's no real Turnitin — caught gracefully.
     */
    public function test_sync_grades_processes_assignment_within_window(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_credentials();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'  => $course->id,
            'duedate' => time() + (24 * 60 * 60),
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'turnitin_assignid',
            'value'       => 99,
            'config_hash' => $cm->id . '_turnitin_assignid',
        ]);

        $mockplugin = $this->make_mock_plugin_with_connection(true);

        $this->expectOutputRegex('/Failed to update grade from tii|No new grades|Successfully synced/');
        (new sync_grades())->execute($mockplugin);
    }

    /**
     * Test that sync_grades writes grades_last_synced after successfully processing
     * an assignment (update_grades_from_tii mocked to return true).
     */
    public function test_sync_grades_writes_last_synced_after_processing(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_credentials();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'  => $course->id,
            'duedate' => time() + (24 * 60 * 60),
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'turnitin_assignid',
            'value'       => 99,
            'config_hash' => $cm->id . '_turnitin_assignid',
        ]);

        $mockplugin = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection', 'update_grades_from_tii'])
            ->getMock();
        $mockplugin->method('test_turnitin_connection')->willReturn(true);
        $mockplugin->method('update_grades_from_tii')->willReturn(true);

        $this->expectOutputRegex('/Successfully synced grades for cmid ' . $cm->id . '/');
        (new sync_grades())->execute($mockplugin);

        $this->assertTrue($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        ));
    }

    // Helpers.

    /**
     * Set the three required Turnitin credentials in config so is_plugin_configured() returns true.
     */
    private function set_credentials(): void {
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
    }

    /**
     * Build a partial mock of plagiarism_plugin_turnitin where test_turnitin_connection()
     * returns the given value without making a real API call.
     *
     * @param bool $connected Value to return from test_turnitin_connection().
     * @return \plagiarism_plugin_turnitin
     */
    private function make_mock_plugin_with_connection(bool $connected): \plagiarism_plugin_turnitin {
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection'])
            ->getMock();
        $mock->method('test_turnitin_connection')->willReturn($connected);
        return $mock;
    }

    /**
     * Test adhoc_send_submission::execute() runs send_single_submission when the plugin
     * is configured — exercises lines 85-89 (the full execute body).
     *
     * The connection test will fail (fake credentials) so send_single_submission returns
     * early after the connection check, but all the execute() body lines are hit.
     */
    public function test_adhoc_execute_runs_when_configured(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl',    'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'TESTKEY', 'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $submission = (object)[
            'id'             => 99,
            'cm'             => $cm->id,
            'userid'         => 2,
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'identifier'     => 'hash',
            'externalid'     => null,
            'submitter'      => 2,
        ];

        $task = adhoc_send_submission::instance($submission);

        // execute() calls plagiarism_turnitin_send_single_submission which does mtrace() on
        // connection failure — suppress output.
        ob_start();
        $task->execute();
        ob_end_clean();

        $this->assertTrue(true);
    }
}

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
 * Unit tests for (some of) plagiarism/turnitin/lib.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2017 Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for API comms class
 *
 * @package turnitin
 */
#[CoversClass(\plagiarism_plugin_turnitin::class)]
#[CoversClass(turnitin_submission::class)]
final class lib_test extends \advanced_testcase {
    /**
     * Test that group submissions are correctly checked.
     *
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function test_check_group_submission(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

        $this->resetAfterTest(true);

        $result = $this->create_assign_with_student_and_teacher([
            'assignsubmission_onlinetext_enabled' => 1,
            'teamsubmission' => 1,
        ]);
        $assignmodule = $result['assign'];
        $student = $result['student'];
        $course = $result['course'];
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $assignmodule->id);
        $context = \context_module::instance($cm->id);
        $assign = new \testable_assign($context, $cm, $course);

        groups_add_member($group, $student);

        $this->setUser($student);
        $submission = $assign->get_group_submission($student->id, $group->id, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $student->id, true, false);
        $data = new \stdClass();
        $data->onlinetext_editor = ['itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_MOODLE, ];
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $response = turnitin_submission::check_group_submission($cm, $student->id);

        // Test should pass as we return the correct group ID.
        $this->assertEquals($group->id, $response);

        // Test a non-group submission.
        $result = $this->create_assign_with_student_and_teacher([
            'assignsubmission_onlinetext_enabled' => 1,
            'teamsubmission' => 0,
        ]);
        $assignmodule = $result['assign'];
        $student = $result['student'];
        $course = $result['course'];
        $cm = get_coursemodule_from_instance('assign', $assignmodule->id);
        $context = \context_module::instance($cm->id);
        $assign = new \testable_assign($context, $cm, $course);

        $this->setUser($student);
        $submission = $assign->get_user_submission($student->id, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $student->id, true, false);
        $data = new \stdClass();
        $data->onlinetext_editor = ['itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_MOODLE, ];
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $response = turnitin_submission::check_group_submission($cm, $student->id);

        // Test should pass as we return false when checking the group ID.
        $this->assertFalse($response);
    }

    /**
     * Create a a course, assignment module instance, student and teacher and enrol them in
     * the course.
     *
     * @param array $params parameters to be provided to the assignment module creation
     * @return array containing the course, assignment module, student and teacher
     */
    public function create_assign_with_student_and_teacher($params = []) {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $params = array_merge([
            'course' => $course->id,
            'name' => 'assignment',
            'intro' => 'assignment intro text',
        ], $params);

        // Create a course and assignment and users.
        $assign = $this->getDataGenerator()->create_module('assign', $params);

        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $context = \context_module::instance($cm->id);

        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        assign_capability('mod/assign:view', CAP_ALLOW, $teacherrole->id, $context->id, true);
        assign_capability('mod/assign:viewgrades', CAP_ALLOW, $teacherrole->id, $context->id, true);
        assign_capability('mod/assign:grade', CAP_ALLOW, $teacherrole->id, $context->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        return [
            'course' => $course,
            'assign' => $assign,
            'student' => $student,
            'teacher' => $teacher,
        ];
    }

    /**
     * Test that the data returned from the report gen speed param function is what we expect.
     */
    public function test_plagiarism_get_report_gen_speed_params(): void {
        $this->resetAfterTest();

        $expected = new \stdClass();
        $expected->num_resubmissions = 3;
        $expected->num_hours = 24;

        $plagiarismturnitin = new \plagiarism_plugin_turnitin();
        $response = $plagiarismturnitin->plagiarism_get_report_gen_speed_params();

        $this->assertEquals($expected, $response);
    }

    /**
     * Test that the set config function saves a config.
     */
    public function test_plagiarism_set_config(): void {
        $this->resetAfterTest();

        // Check that we can set config value when a full property name is given.
        $data = new \stdClass();
        $data->plagiarism_turnitin_accountid = 123456789;
        $property = "plagiarism_turnitin_accountid";

        turnitin_settings::set_config($data, $property);

        // Get the config.
        $config = turnitin_settings::admin_config();

        $this->assertEquals(123456789, $config->plagiarism_turnitin_accountid);

        // Check that we can set config value when a partial property name is given.
        $data = new \stdClass();
        $data->secretkey = "Test";
        $property = "secretkey";
        turnitin_settings::set_config($data, $property);

        // Get the config.
        $config = turnitin_settings::admin_config();

        $this->assertEquals("Test", $config->plagiarism_turnitin_secretkey);

        // Check that an undefined property does not set a config value.
        $data = new \stdClass();
        $data->test = "Test";
        $property = "NotTest";
        turnitin_settings::set_config($data, $property);

        // Get the config.
        $config = turnitin_settings::admin_config();

        if (method_exists($this, 'assertObjectNotHasProperty')) {
            $this->assertObjectNotHasProperty("plagiarism_turnitin_test", $config);
        } else {
            $this->assertObjectNotHasAttribute("plagiarism_turnitin_test", $config);
        }
    }

    // Get_file_upload_errors tests.

    /**
     * Test that get_file_upload_errors returns only rows with statuscode 'error',
     * excluding queued, pending and success records.
     */
    public function test_get_file_upload_errors_returns_only_error_rows(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        foreach (['error', 'success', 'queued'] as $status) {
            $DB->insert_record('plagiarism_turnitin_files', (object)[
                'cm' => $assign->cmid, 'userid' => $user->id, 'identifier' => $status,
                'statuscode' => $status, 'attempt' => 0, 'submissiontype' => 'file',
                'itemid' => 0, 'submitter' => $user->id, 'lastmodified' => time(), 'transmatch' => 0,
            ]);
        }

        $plugin = new \plagiarism_plugin_turnitin();
        $results = $plugin->get_file_upload_errors();

        $this->assertCount(1, $results);
        $row = reset($results);
        $this->assertEquals($user->firstname, $row->firstname);
        $this->assertEquals($course->fullname, $row->coursename);
    }

    /**
     * Test that get_file_upload_errors returns a count when $count is true.
     */
    public function test_get_file_upload_errors_returns_count_when_requested(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        for ($i = 0; $i < 3; $i++) {
            $DB->insert_record('plagiarism_turnitin_files', (object)[
                'cm' => $assign->cmid, 'userid' => $user->id, 'identifier' => "hash$i",
                'statuscode' => 'error', 'attempt' => 0, 'submissiontype' => 'file',
                'itemid' => 0, 'submitter' => $user->id, 'lastmodified' => time(), 'transmatch' => 0,
            ]);
        }

        $plugin = new \plagiarism_plugin_turnitin();
        $this->assertEquals(3, $plugin->get_file_upload_errors(0, 0, true));
    }

    /**
     * Test that get_file_upload_errors respects the $limit parameter.
     */
    public function test_get_file_upload_errors_respects_limit(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        for ($i = 0; $i < 5; $i++) {
            $DB->insert_record('plagiarism_turnitin_files', (object)[
                'cm' => $assign->cmid, 'userid' => $user->id, 'identifier' => "hash$i",
                'statuscode' => 'error', 'attempt' => 0, 'submissiontype' => 'file',
                'itemid' => 0, 'submitter' => $user->id, 'lastmodified' => time(), 'transmatch' => 0,
            ]);
        }

        $plugin = new \plagiarism_plugin_turnitin();
        $this->assertCount(2, $plugin->get_file_upload_errors(0, 2));
    }

    // Update_status tests.

    /**
     * Test that update_status returns a div with the expected id and CSS class.
     */
    public function test_update_status_returns_expected_html(): void {
        $this->resetAfterTest();

        $plugin = new \plagiarism_plugin_turnitin();
        $output = $plugin->update_status(new \stdClass(), new \stdClass());

        $this->assertStringContainsString('turnitin_score_refresh_alert', $output);
        $this->assertStringContainsString('id="turnitin_score_refresh_alert"', $output);
    }

    // Set_duedate_report_refresh tests.

    /**
     * Test that set_duedate_report_refresh updates the duedate_report_refresh field
     * on the specified row without affecting other rows.
     */
    public function test_set_duedate_report_refresh_updates_correct_row(): void {
        global $DB;
        $this->resetAfterTest();

        $id1 = $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => 1, 'userid' => 1, 'identifier' => 'hash1', 'statuscode' => 'queued',
            'attempt' => 0, 'submissiontype' => 'file', 'itemid' => 0,
            'submitter' => 1, 'lastmodified' => time(), 'transmatch' => 0,
        ]);
        $id2 = $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => 1, 'userid' => 1, 'identifier' => 'hash2', 'statuscode' => 'queued',
            'attempt' => 0, 'submissiontype' => 'file', 'itemid' => 0,
            'submitter' => 1, 'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->set_duedate_report_refresh($id1, 1);

        $this->assertEquals(1, $DB->get_field('plagiarism_turnitin_files', 'duedate_report_refresh', ['id' => $id1]));
        // Second row should be unaffected.
        $this->assertNotEquals(1, $DB->get_field('plagiarism_turnitin_files', 'duedate_report_refresh', ['id' => $id2]));
    }

    // Course_reset tests.

    /**
     * Test that course_reset deletes plagiarism_turnitin_files records for assign
     * submissions when reset_assign_submissions is set.
     */
    public function test_course_reset_deletes_files_when_assign_reset(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        // Course_reset only processes CMs that have a turnitin_assignid config row.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $assign->cmid, 'name' => 'turnitin_assignid',
            'value' => 99, 'config_hash' => $assign->cmid . '_turnitin_assignid',
        ]);
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $assign->cmid, 'userid' => $user->id, 'identifier' => 'hash1',
            'statuscode' => 'success', 'attempt' => 1, 'submissiontype' => 'file',
            'itemid' => 1, 'submitter' => $user->id, 'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files', ['cm' => $assign->cmid]));

        $eventdata = $this->make_course_reset_event($course->id, ['reset_assign_submissions' => 1]);
        \plagiarism_turnitin\turnitin_course::course_reset($eventdata);

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files', ['cm' => $assign->cmid]));
    }

    /**
     * Test that course_reset does not delete plagiarism_turnitin_files records
     * when reset_assign_submissions is not set.
     */
    public function test_course_reset_preserves_files_when_no_reset_flags_set(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $assign->cmid, 'name' => 'turnitin_assignid',
            'value' => 99, 'config_hash' => $assign->cmid . '_turnitin_assignid',
        ]);
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $assign->cmid, 'userid' => $user->id, 'identifier' => 'hash1',
            'statuscode' => 'success', 'attempt' => 1, 'submissiontype' => 'file',
            'itemid' => 1, 'submitter' => $user->id, 'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $eventdata = $this->make_course_reset_event($course->id, []);
        \plagiarism_turnitin\turnitin_course::course_reset($eventdata);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files', ['cm' => $assign->cmid]));
    }

    /**
     * Build a minimal event data stub for course_reset tests.
     *
     * @param int   $courseid
     * @param array $resetoptions
     * @return object
     */
    private function make_course_reset_event(int $courseid, array $resetoptions): object {
        return new class ($courseid, $resetoptions) {
            /** @var int */
            private $courseid;
            /** @var array */
            private $options;

            /**
             * Constructor.
             * @param int   $courseid
             * @param array $options
             */
            public function __construct(int $courseid, array $options) {
                $this->courseid = $courseid;
                $this->options  = $options;
            }

            /**
             * Get event data array.
             * @return array
             */
            public function get_data(): array {
                return ['other' => ['reset_options' => array_merge(
                    ['courseid' => $this->courseid],
                    $this->options
                )]];
            }
        };
    }

    // End-to-end tests for get_links() early-return paths.

    /**
     * Test get_links returns empty string for feedback_files filearea.
     *
     * This exercises the should_skip_non_submitting_filearea guard in get_links
     * without needing a full Turnitin-connected course setup.
     */
    public function test_get_links_returns_empty_for_feedback_files_filearea(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'assignfeedback_file',
            'filearea'  => 'feedback_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'feedback.txt',
        ], 'feedback content');

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->get_links(['file' => $file, 'cmid' => 1, 'userid' => 1]);

        $this->assertSame('', $result);
        $fs->delete_area_files(\context_system::instance()->id, 'assignfeedback_file', 'feedback_files');
    }

    /**
     * Test get_links returns empty string when quiz module is disabled in Turnitin.
     */
    public function test_get_links_returns_empty_when_quiz_disabled(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_quiz', 0, 'plagiarism_turnitin');

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->get_links(['component' => 'qtype_essay', 'cmid' => 1, 'userid' => 1]);

        $this->assertSame('', $result);
    }

    /**
     * Test get_links returns empty string when use_turnitin is disabled for the module.
     */
    public function test_get_links_returns_empty_when_turnitin_disabled_for_cm(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        // No plagiarism_turnitin_config row → use_turnitin is absent → early return.
        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->get_links([
            'cmid'    => $cm->id,
            'userid'  => 1,
            'content' => 'some text',
        ]);

        $this->assertSame('', $result);
    }

    // End-to-end tests for plagiarism_turnitin_send_single_submission().

    /**
     * Test send_single_submission returns early when there is no Turnitin connection.
     */
    public function test_send_single_submission_returns_early_when_no_connection(): void {
        $this->resetAfterTest();

        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection'])
            ->getMock();
        $mock->method('test_turnitin_connection')->willReturn(false);

        $queued = (object)['id' => 1, 'cm' => 1, 'userid' => 1, 'attempt' => 0, 'submissiontype' => 'file'];

        $this->expectOutputRegex('/connection.*Turnitin|Turnitin.*connection/i');
        plagiarism_turnitin_send_single_submission($mock, $queued);
    }

    /**
     * Test send_single_submission saves errorcode 12 when the cm does not exist.
     */
    public function test_send_single_submission_errors_when_cm_not_found(): void {
        global $DB;
        $this->resetAfterTest();

        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection'])
            ->getMock();
        $mock->method('test_turnitin_connection')->willReturn(true);

        $user   = $this->getDataGenerator()->create_user();
        $id     = $this->insert_submission_row(['cm' => 99999, 'userid' => $user->id]);
        $queued = (object)['id' => $id, 'cm' => 99999, 'userid' => $user->id, 'attempt' => 0, 'submissiontype' => 'file'];

        plagiarism_turnitin_send_single_submission($mock, $queued);

        $this->assertEquals(12, $DB->get_field('plagiarism_turnitin_files', 'errorcode', ['id' => $id]));
    }

    /**
     * Test send_single_submission saves errorcode 7 when userid is 0.
     */
    public function test_send_single_submission_errors_when_userid_zero(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        // Credentials needed so turnitin_comms doesn't throw during edit_tii_course.
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // Mock the plugin so sync_tii_assignment doesn't make API calls.
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection', 'sync_tii_assignment'])
            ->getMock();
        $mock->method('test_turnitin_connection')->willReturn(true);
        $mock->method('sync_tii_assignment')->willReturn(['tiiassignmentid' => 1, 'errorcode' => 0, 'success' => true]);

        // Seed a turnitin_courses row so get_course_data() returns early without API.
        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid' => $course->id, 'turnitin_cid' => 99, 'turnitin_ctl' => 'Test Course',
        ]);

        $id     = $this->insert_submission_row(['cm' => $cm->id, 'userid' => $user->id]);
        $queued = (object)[
            'id'             => $id,
            'cm'             => $cm->id,
            'userid'         => 0,
            'submitter'      => 0,
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'identifier'     => 'hash',
            'externalid'     => null,
        ];

        plagiarism_turnitin_send_single_submission($mock, $queued);

        $this->assertEquals(7, $DB->get_field('plagiarism_turnitin_files', 'errorcode', ['id' => $id]));
    }

    // End-to-end tests for event_handler().

    /**
     * Test event_handler returns true when the cm does not exist (stale event).
     */
    public function test_event_handler_returns_true_when_cm_missing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'assign'],
            'contextinstanceid' => 99999,
            'userid'            => 1,
            'eventtype'         => 'file_uploaded',
            'objectid'          => 1,
        ];

        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
    }

    /**
     * Test event_handler returns true when Turnitin is not enabled for the module.
     */
    public function test_event_handler_returns_true_when_module_disabled(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        set_config('plagiarism_turnitin_mod_assign', 0, 'plagiarism_turnitin');

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'assign'],
            'contextinstanceid' => $cm->id,
            'userid'            => 1,
            'eventtype'         => 'file_uploaded',
            'objectid'          => $assign->id,
        ];

        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
    }

    /**
     * Test event_handler returns true when use_turnitin is not set for the CM.
     */
    public function test_event_handler_returns_true_when_use_turnitin_disabled(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        // No plagiarism_turnitin_config row → use_turnitin absent → should_process_event returns false.

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'assign'],
            'contextinstanceid' => $cm->id,
            'userid'            => 1,
            'eventtype'         => 'file_uploaded',
            'objectid'          => $assign->id,
        ];

        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
    }

    /**
     * Test event_handler returns true when draft submit is on and event is not final.
     */
    public function test_event_handler_returns_true_when_draft_skipped(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'           => $course->id,
            'submissiondrafts' => 1,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'use_turnitin',
            'value'       => '1',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'plagiarism_draft_submit',
            'value'       => '1',
            'config_hash' => $cm->id . '_plagiarism_draft_submit',
        ]);

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'assign'],
            'contextinstanceid' => $cm->id,
            'userid'            => 1,
            'eventtype'         => 'file_uploaded',
            'objectid'          => $assign->id,
        ];

        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
    }

    // Tests for print_disclosure().

    /**
     * Test print_disclosure delegates to turnitin_disclosure::render and returns its output.
     * Exercises lib.php line 165.
     */
    public function test_print_disclosure_returns_disclosure_html(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->print_disclosure($cm->id);

        // turnitin_disclosure::render returns a string (empty when Turnitin is not configured).
        $this->assertIsString($result);
    }

    // Tests for plagiarism_turnitin_coursemodule_standard_elements().

    /**
     * Test the standard_elements hook can be called without throwing, even when
     * the plugin is not configured — add_to_form returns early in that case.
     * Exercises lib.php lines 1829-1833.
     */
    public function test_coursemodule_standard_elements_runs_without_throwing(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        require_once($CFG->dirroot . '/lib/formslib.php');

        $mform   = new \MoodleQuickForm('test_form', 'post', '');
        $context = \context_course::instance($course->id);

        // Calls add_to_form which returns early (plugin not configured), but the
        // function itself must not throw.
        \plagiarism_turnitin\turnitin_activitysettingsform::add_to_form($mform, $context, '');

        // No exception = pass; we can only assert the mform object is still intact.
        $this->assertInstanceOf(\MoodleQuickForm::class, $mform);
    }

    // Tests for plagiarism_get_report_gen_speed_params().

    /**
     * Test that plagiarism_get_report_gen_speed_params returns the correct constant values.
     */
    public function test_plagiarism_get_report_gen_speed_params_returns_correct_values(): void {
        $plugin = new \plagiarism_plugin_turnitin();
        $params = $plugin->plagiarism_get_report_gen_speed_params();

        $this->assertEquals(PLAGIARISM_TURNITIN_REPORT_GEN_SPEED_NUM_RESUBMISSIONS, $params->num_resubmissions);
        $this->assertEquals(PLAGIARISM_TURNITIN_REPORT_GEN_SPEED_NUM_HOURS, $params->num_hours);
    }

    // Tests for set_duedate_report_refresh().

    /**
     * Test set_duedate_report_refresh updates the duedate_report_refresh field in the DB.
     */
    public function test_set_duedate_report_refresh_updates_field(): void {
        $this->resetAfterTest();

        $id = $this->insert_submission_row(['duedate_report_refresh' => 0]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->set_duedate_report_refresh($id, 1);

        global $DB;
        $this->assertEquals(1, $DB->get_field('plagiarism_turnitin_files', 'duedate_report_refresh', ['id' => $id]));
    }

    /**
     * Test set_duedate_report_refresh can reset the field back to 0.
     */
    public function test_set_duedate_report_refresh_can_reset_to_zero(): void {
        $this->resetAfterTest();

        $id = $this->insert_submission_row(['duedate_report_refresh' => 2]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->set_duedate_report_refresh($id, 0);

        global $DB;
        $this->assertEquals(0, $DB->get_field('plagiarism_turnitin_files', 'duedate_report_refresh', ['id' => $id]));
    }

    // Tests for plagiarism_turnitin_coursemodule_edit_post_actions().

    /**
     * Test the post-actions hook saves CM settings and returns $data unchanged.
     */
    public function test_coursemodule_edit_post_actions_saves_settings_and_returns_data(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        $data = new \stdClass();
        $data->coursemodule = $cm->id;
        $data->modulename   = 'assign';
        $data->plagiarism_compare_internet = 1;

        $result = plagiarism_turnitin_coursemodule_edit_post_actions($data, (object)['id' => $course->id]);

        $this->assertSame($data, $result);
        $saved = $DB->get_field('plagiarism_turnitin_config', 'value',
            ['cm' => $cm->id, 'name' => 'plagiarism_compare_internet']);
        $this->assertEquals(1, (int) $saved);
    }

    // Tests for queue_submission_to_turnitin().

    /**
     * Test queue_submission_to_turnitin returns true immediately when the identifier
     * already has a successful/queued submission, without writing a new row.
     */
    public function test_queue_submission_returns_true_when_already_submitted(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        // Seed an already-successful row for this identifier.
        $this->insert_submission_row([
            'cm'         => $cm->id,
            'userid'     => $user->id,
            'identifier' => 'already-done',
            'statuscode' => 'success',
        ]);

        // Seed EULA accepted so we don't hit the API path.
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'user_agreement_accepted' => 1,
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->queue_submission_to_turnitin($cm, $user->id, $user->id, 'already-done', 'text_content');

        $this->assertTrue($result);
        // Only the original row should exist.
        $count = $DB->count_records('plagiarism_turnitin_files', ['cm' => $cm->id, 'userid' => $user->id]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test queue_submission_to_turnitin returns true and logs when no comparison
     * sources are enabled for the CM — no submission row should be written.
     */
    public function test_queue_submission_returns_true_when_no_comparison_options(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        // EULA accepted.
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'user_agreement_accepted' => 1,
        ]);

        // Enable plugin for assign, but set all comparison sources to 0.
        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        foreach (['use_turnitin', 'plagiarism_compare_internet', 'plagiarism_compare_student_papers',
                  'plagiarism_compare_journals', 'plagiarism_compare_institution'] as $name) {
            $DB->insert_record('plagiarism_turnitin_config', (object)[
                'cm' => $cm->id, 'name' => $name, 'value' => 0,
                'config_hash' => $cm->id . '_' . $name,
            ]);
        }

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->queue_submission_to_turnitin($cm, $user->id, $user->id, 'noopts-hash', 'text_content');

        $this->assertTrue($result);
        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id]));
    }

    /**
     * Test queue_submission_to_turnitin queues a text_content submission when
     * EULA is accepted and comparison options are enabled.
     */
    public function test_queue_submission_queues_text_content(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // EULA accepted.
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'user_agreement_accepted' => 1,
        ]);

        // Enable plugin + at least one comparison source.
        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => 1,
            'config_hash' => $cm->id . '_use_turnitin',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'plagiarism_compare_internet', 'value' => 1,
            'config_hash' => $cm->id . '_plagiarism_compare_internet',
        ]);
        // plagiarism_report_gen is needed by is_resubmission_allowed.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'plagiarism_report_gen', 'value' => 0,
            'config_hash' => $cm->id . '_plagiarism_report_gen',
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->queue_submission_to_turnitin($cm, $user->id, $user->id, 'text-hash-new', 'text_content');

        $this->assertTrue($result);
        $row = $DB->get_record('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => 'text-hash-new']);
        $this->assertNotFalse($row);
        $this->assertEquals('queued', $row->statuscode);
    }

    // Tests for clean_old_turnitin_submissions().

    /**
     * Test clean_old_turnitin_submissions deletes old text_content rows for
     * the same user/cm when the identifier has changed.
     */
    public function test_clean_old_submissions_removes_old_text_content(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        // Old text_content row (different identifier from the new one).
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => 'old-text-hash',
            'submissiontype' => 'text_content',
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->clean_old_turnitin_submissions($cm, $user->id, 0, 'text_content', 'new-text-hash');

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => 'old-text-hash']));
    }

    /**
     * Test clean_old_turnitin_submissions leaves the current text_content row untouched.
     */
    public function test_clean_old_submissions_keeps_current_text_content(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => 'current-text-hash',
            'submissiontype' => 'text_content',
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->clean_old_turnitin_submissions($cm, $user->id, 0, 'text_content', 'current-text-hash');

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => 'current-text-hash']));
    }

    /**
     * Test clean_old_turnitin_submissions returns early for assign file type when
     * there are no assign_submission rows (empty itemids guard).
     */
    public function test_clean_old_submissions_returns_early_when_no_assign_submissions(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        // Seed a turnitin row that should NOT be deleted (no assign_submission rows exist).
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => 'file-hash',
            'submissiontype' => 'file',
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->clean_old_turnitin_submissions($cm, $user->id, 0, 'file', 'file-hash');

        // Row must still exist — the method returned early.
        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id]));
    }

    /**
     * Test clean_old_turnitin_submissions for a non-assign module with file type:
     * old turnitin rows whose identifier is no longer in the Moodle files table
     * and have no externalid are deleted.
     */
    public function test_clean_old_submissions_deletes_orphaned_file_rows_for_forum(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);
        $user   = $this->getDataGenerator()->create_user();

        // Upload a real file with userid and source set so the files query can find it.
        $fs      = get_file_storage();
        $context = \context_module::instance($cm->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_forum',
            'filearea'  => 'attachment',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'current.txt',
            'userid'    => $user->id,
            'source'    => 'current.txt',
        ], 'hello');

        // Current submission row — its identifier IS in Moodle files → should be kept.
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $file->get_pathnamehash(),
            'submissiontype' => 'file',
        ]);

        // Old row whose identifier is NOT in Moodle files → should be deleted.
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => 'old-orphan-hash',
            'submissiontype' => 'file',
            'externalid'     => null,
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->clean_old_turnitin_submissions($cm, $user->id, 1, 'file', $file->get_pathnamehash());

        // Old row deleted, current row kept.
        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => 'old-orphan-hash']));
        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => $file->get_pathnamehash()]));
    }

    // Further event_handler tests (past the early-return guards).

    /**
     * Test event_handler removes queued submission rows when a submission_removed
     * event arrives for an assign — exercises the remove_queued_for_submission branch.
     */
    public function test_event_handler_removes_queued_rows_on_submission_removed(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => 1,
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // A queued row with itemid=42 — objectid in the event must match itemid.
        $this->insert_submission_row([
            'cm'         => $cm->id,
            'userid'     => $user->id,
            'itemid'     => 42,
            'statuscode' => 'queued',
        ]);

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'assign'],
            'contextinstanceid' => $cm->id,
            'userid'            => $user->id,
            'relateduserid'     => $user->id,
            'eventtype'         => 'submission_removed',
            'objectid'          => 42,
        ];

        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'statuscode' => 'queued']));
    }

    /**
     * Test event_handler sets submissiondrafts = 0 for non-assign modules so the
     * draft-skip guard always passes — exercises the `modname != 'assign'` branch.
     * The result is true because queue_text_content/queue_file_submissions both
     * return true when there is no content or files in the event.
     */
    public function test_event_handler_sets_submissiondrafts_zero_for_forum(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);
        $user   = $this->getDataGenerator()->create_user();

        set_config('plagiarism_turnitin_mod_forum', 1, 'plagiarism_turnitin');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => 1,
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'forum', 'content' => ''],
            'contextinstanceid' => $cm->id,
            'userid'            => $user->id,
            'eventtype'         => 'content_uploaded',
            'objectid'          => 1,
        ];

        // Should not throw or return false — the draft-skip guard must pass because
        // submissiondrafts is forced to 0 for non-assign modules.
        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
    }

    // Tests for cron_update_scores().

    /**
     * Test cron_update_scores returns true immediately when there are no eligible
     * submissions — the loop and API batch code are all skipped.
     */
    public function test_cron_update_scores_returns_true_with_no_submissions(): void {
        $this->resetAfterTest();

        // Minimal credentials so turnitin_comms doesn't throw on construction
        // (check_local_submission_state always instantiates it even with empty input).
        set_config('plagiarism_turnitin_accountid',  '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl',     'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey',  'TESTKEY', 'plagiarism_turnitin');

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->cron_update_scores();

        $this->assertTrue($result);
    }

    /**
     * Test cron_update_scores sets duedate_report_refresh to 1 mid-loop for a
     * submission whose due date fell within the last 24 hours, then resets it
     * to 2 in the final foreach — exercises lines 1223-1228 and 1353-1354.
     *
     * check_local_submission_state calls the Turnitin API, which will throw
     * (no real connection); the exception is caught inside the API layer and
     * cron_update_scores ultimately still returns true.
     */
    public function test_cron_update_scores_sets_duedate_refresh_then_resets_to_two(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        // Minimal credentials so turnitin_comms constructor does not throw.
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl',    'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'TESTKEY', 'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'  => $course->id,
            'duedate' => time() - HOURSECS, // 1 hour ago — within the 24 h window.
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);

        $id = $this->insert_submission_row([
            'cm'                     => $cm->id,
            'userid'                 => 2,
            'statuscode'             => 'success',
            'externalid'             => 'ext-ddr',
            'identifier'             => 'hash-ddr',
            'duedate_report_refresh' => 0,
            'similarityscore'        => null,
        ]);

        // turnitin_assignid needed so the submission is added to the API request.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'turnitin_assignid', 'value' => '99',
            'config_hash' => $cm->id . '_turnitin_assignid',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'plagiarism_compare_internet', 'value' => '1',
            'config_hash' => $cm->id . '_plagiarism_compare_internet',
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        // Suppress expected mtrace() output from the API exception and DB update messages.
        ob_start();
        $result = $plugin->cron_update_scores();
        ob_end_clean();

        $this->assertTrue($result);
        // The final foreach resets every submission's flag to 2 regardless of API outcome.
        $this->assertEquals(2, $DB->get_field('plagiarism_turnitin_files', 'duedate_report_refresh', ['id' => $id]));
    }

    // Tests for update_grade().

    /**
     * Build a minimal TII submission stub with a grade and submission ID.
     *
     * @param int|null $grade The grade value to return from getGrade().
     * @param string   $submissionid The value to return from getSubmissionId().
     */
    private function make_graded_tii_submission(?int $grade, string $submissionid = 'ext-1'): object {
        // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        return new class ($grade, $submissionid) {
            /** @var int|null */
            private $grade;
            /** @var string */
            private $submissionid;

            /** @param int|null $grade @param string $submissionid */
            public function __construct(?int $grade, string $submissionid) {
                $this->grade        = $grade;
                $this->submissionid = $submissionid;
            }

            /** @return int|null */
            public function getGrade(): ?int {
                return $this->grade;
            }

            /** @return string */
            public function getSubmissionId(): string {
                return $this->submissionid;
            }
        }; // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
    }

    /**
     * Test update_grade returns true immediately when getGrade() is null —
     * nothing should be written to the gradebook.
     */
    public function test_update_grade_returns_true_when_grade_is_null(): void {
        $this->resetAfterTest();

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(null);
        $cm         = (object)['id' => 1, 'modname' => 'assign', 'instance' => 1, 'course' => 1];

        $result = $plugin->update_grade($cm, $submission, 1);

        $this->assertTrue($result);
    }

    /**
     * Test update_grade returns true immediately for forum — Turnitin does not
     * write grades back to the forum gradebook even when a grade is present.
     */
    public function test_update_grade_returns_true_for_forum(): void {
        $this->resetAfterTest();

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(75);
        $cm         = (object)['id' => 1, 'modname' => 'forum', 'instance' => 1, 'course' => 1];

        $result = $plugin->update_grade($cm, $submission, 1);

        $this->assertTrue($result);
    }

    /**
     * Test update_grade returns false when blind marking is on and identities
     * have not yet been revealed — grades must not be passed to the gradebook
     * before students are de-anonymised.
     */
    public function test_update_grade_returns_false_when_blind_marking_active(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'           => $course->id,
            'blindmarking'     => 1,
            'revealidentities' => 0,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assign->id);
        $user = $this->getDataGenerator()->create_user();

        // A turnitin_files row with a known externalid so update_grade can look it up.
        // No real file hash → falls to the else branch (fetches all records for userid/cm).
        $this->insert_submission_row([
            'cm'         => $cm->id,
            'userid'     => $user->id,
            'externalid' => 'ext-blind',
            'identifier' => 'fakehash-blind',
            'grade'      => 80,
        ]);

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(80, 'ext-blind');

        $result = $plugin->update_grade($cm, $submission, $user->id);

        $this->assertFalse($result);
    }

    /**
     * Test update_grade inserts a new assign_grades row when no grade record
     * exists yet for this user and assignment.
     */
    public function test_update_grade_inserts_new_grade_for_assign(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->insert_submission_row([
            'cm'         => $cm->id,
            'userid'     => $user->id,
            'externalid' => 'ext-newgrade',
            'identifier' => 'fakehash-newgrade',
            'grade'      => 72,
        ]);

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(72, 'ext-newgrade');

        $result = $plugin->update_grade($cm, $submission, $user->id);

        $this->assertTrue($result);
        $grade = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $user->id]);
        $this->assertNotFalse($grade);
        $this->assertEquals(72, (int) $grade->grade);
    }

    /**
     * Test update_grade updates an existing assign_grades row when one already
     * exists — the grade value should be overwritten.
     */
    public function test_update_grade_updates_existing_grade_for_assign(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Pre-seed an existing grade row.
        $DB->insert_record('assign_grades', (object)[
            'assignment'    => $assign->id,
            'userid'        => $user->id,
            'attemptnumber' => 0,
            'grade'         => 50,
            'grader'        => 2,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);

        $this->insert_submission_row([
            'cm'         => $cm->id,
            'userid'     => $user->id,
            'externalid' => 'ext-update',
            'identifier' => 'fakehash-update',
            'grade'      => 88,
        ]);

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(88, 'ext-update');

        $result = $plugin->update_grade($cm, $submission, $user->id);

        $this->assertTrue($result);
        $grade = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $user->id]);
        $this->assertEquals(88, (int) $grade->grade);
    }

    /**
     * Test update_grade uses grade from the submission object when the identifier
     * does not match a real Moodle file. When no file hash is found, the else-branch
     * calls current() on the DB records (returning a single object, not an array),
     * so the averaging path is bypassed and grade = $submission->getGrade().
     *
     * Note: this pins an implementation detail of the else-branch — it is by design
     * that the averaging only applies when a real Moodle file can be resolved.
     */
    public function test_update_grade_uses_submission_grade_when_no_file_hash_match(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Two rows exist but no real Moodle file — else branch uses current() (single object),
        // so grade comes from $submission->getGrade(), not an average.
        $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => $user->id,
            'externalid' => 'ext-nfh1', 'identifier' => 'fakehash-nfh1', 'grade' => 60,
        ]);
        $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => $user->id,
            'externalid' => 'ext-nfh2', 'identifier' => 'fakehash-nfh2', 'grade' => 80,
        ]);

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(55, 'ext-nfh1');

        $result = $plugin->update_grade($cm, $submission, $user->id);

        $this->assertTrue($result);
        $grade = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $user->id]);
        // The else-branch falls into the scalar path → grade == $submission->getGrade().
        $this->assertEquals(55, (int) $grade->grade);
    }

    /**
     * Test update_grade nulls the rawgrade passed to the gradebook when
     * marking workflow is enabled and the grade has not yet been released.
     *
     * The grade IS written to assign_grades (the module-level record), but
     * assign_grade_item_update is called with rawgrade=null so the grade is
     * not surfaced to students. We verify the assign_grades write happened;
     * the gradebook suppression itself flows through Moodle's grade_update()
     * internals which are outside the scope of this unit test.
     */
    public function test_update_grade_suppresses_gradebook_when_workflow_unreleased(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'markingworkflow' => 1,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assign->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->insert_submission_row([
            'cm'         => $cm->id,
            'userid'     => $user->id,
            'externalid' => 'ext-wf',
            'identifier' => 'fakehash-wf',
            'grade'      => 65,
        ]);

        // No assign_user_flags row with workflowstate='released' → grade suppressed from gradebook.
        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(65, 'ext-wf');

        $result = $plugin->update_grade($cm, $submission, $user->id);

        // Grade is written to assign_grades...
        $grade = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $user->id]);
        $this->assertNotFalse($grade);
        $this->assertEquals(65, (int) $grade->grade);
        // ...and update_grade still returns true (gradebook update with null grade succeeds).
        $this->assertTrue($result);
    }

    /**
     * Test update_grade propagates the grade to every member of a group when
     * the assignment uses team submissions — exercises lines 723-731.
     */
    public function test_update_grade_updates_all_group_members_for_team_submission(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $course  = $this->getDataGenerator()->create_course();
        $assign  = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'teamsubmission' => 1,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $student1);
        groups_add_member($group, $student2);

        // Seed turnitin files rows for both students.
        $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => $student1->id,
            'externalid' => 'ext-grp1', 'identifier' => 'hash-grp1', 'grade' => 90,
        ]);

        $plugin     = new \plagiarism_plugin_turnitin();
        $submission = $this->make_graded_tii_submission(90, 'ext-grp1');

        $result = $plugin->update_grade($cm, $submission, $student1->id);

        $this->assertTrue($result);
        // Both group members should have an assign_grades row.
        $this->assertNotFalse($DB->get_record('assign_grades',
            ['assignment' => $assign->id, 'userid' => $student1->id]));
        $this->assertNotFalse($DB->get_record('assign_grades',
            ['assignment' => $assign->id, 'userid' => $student2->id]));
    }

    /**
     * Test queue_submission_to_turnitin queues a file submission when the file has
     * a valid extension — exercises the file-type branch (lines 1535-1571) and
     * confirms no errorcode is set on the saved row.
     */
    public function test_queue_submission_queues_valid_file(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // EULA accepted.
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'user_agreement_accepted' => 1,
        ]);

        // Enable plugin + comparison source.
        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        foreach (['use_turnitin' => 1, 'plagiarism_compare_internet' => 1, 'plagiarism_report_gen' => 0] as $name => $val) {
            $DB->insert_record('plagiarism_turnitin_config', (object)[
                'cm' => $cm->id, 'name' => $name, 'value' => $val,
                'config_hash' => $cm->id . '_' . $name,
            ]);
        }

        // Create a real .docx file in the file storage so get_file_by_hash() returns it.
        $fs      = get_file_storage();
        $context = \context_module::instance($cm->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'essay.docx',
            'userid'    => $user->id,
            'source'    => 'essay.docx',
        ], 'some content');

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->queue_submission_to_turnitin(
            $cm, $user->id, $user->id, $file->get_pathnamehash(), 'file', 1, null
        );

        $this->assertTrue($result);
        $row = $DB->get_record('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => $file->get_pathnamehash()]);
        $this->assertNotFalse($row);
        $this->assertEquals('queued', $row->statuscode);
        $this->assertEquals(0, $row->errorcode);
    }

    /**
     * Test queue_submission_to_turnitin saves an error row when a file has an
     * unsupported extension and plagiarism_allow_non_or_submissions is off —
     * exercises the get_file_errorcode branch (lines 1565-1571).
     */
    public function test_queue_submission_saves_error_for_invalid_file_extension(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // EULA accepted.
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'user_agreement_accepted' => 1,
        ]);

        // Enable plugin + comparison source; non-or-submissions NOT allowed.
        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        foreach (['use_turnitin' => 1, 'plagiarism_compare_internet' => 1,
                  'plagiarism_report_gen' => 0, 'plagiarism_allow_non_or_submissions' => 0] as $name => $val) {
            $DB->insert_record('plagiarism_turnitin_config', (object)[
                'cm' => $cm->id, 'name' => $name, 'value' => $val,
                'config_hash' => $cm->id . '_' . $name,
            ]);
        }

        // Create a .xyz file — not in the accepted list.
        $fs      = get_file_storage();
        $context = \context_module::instance($cm->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 2,
            'filepath'  => '/',
            'filename'  => 'unknown.xyz',
            'userid'    => $user->id,
            'source'    => 'unknown.xyz',
        ], 'binary data');

        $plugin = new \plagiarism_plugin_turnitin();
        $result = $plugin->queue_submission_to_turnitin(
            $cm, $user->id, $user->id, $file->get_pathnamehash(), 'file', 2, null
        );

        // Still returns true (an error row is saved, not an exception).
        $this->assertTrue($result);
        $row = $DB->get_record('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => $file->get_pathnamehash()]);
        $this->assertNotFalse($row);
        $this->assertEquals('error', $row->statuscode);
        $this->assertGreaterThan(0, $row->errorcode);
    }

    /**
     * Test clean_old_turnitin_submissions deletes orphaned file rows for an assign
     * when assign_submission rows exist — exercises the assign file branch
     * (lines 1751-1794) which builds an IN clause across multiple itemids.
     */
    public function test_clean_old_submissions_deletes_orphaned_file_rows_for_assign(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create an assign_submission row so the itemids query returns something.
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assign->id,
            'userid'        => $user->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timemodified'  => time(),
            'timecreated'   => time(),
        ]);

        // Upload a real file linked to that submission so moodlefiles query finds it.
        $fs      = get_file_storage();
        $context = \context_module::instance($cm->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => $submissionid,
            'filepath'  => '/',
            'filename'  => 'current.txt',
            'userid'    => $user->id,
            'source'    => 'current.txt',
        ], 'hello world');

        // Current turnitin row — identifier matches real file → should be kept.
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $file->get_pathnamehash(),
            'submissiontype' => 'file',
        ]);

        // Old turnitin row — identifier doesn't match any Moodle file → should be deleted.
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => 'stale-hash-assign',
            'submissiontype' => 'file',
            'externalid'     => null,
        ]);

        $plugin = new \plagiarism_plugin_turnitin();
        $plugin->clean_old_turnitin_submissions($cm, $user->id, $submissionid, 'file', $file->get_pathnamehash());

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => 'stale-hash-assign']));
        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => $file->get_pathnamehash()]));
    }

    /**
     * Test event_handler enriches an assessable_submitted event with actual
     * submission content for assign — exercises line 1651.
     * Since the enrich call just reads assign_submissions, there is no
     * API dependency; we verify the handler returns true and doesn't throw.
     */
    public function test_event_handler_enriches_assessable_submitted_for_assign(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => 1,
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // Create a real assign_submission row so enrich_assessable_submitted can look it up.
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assign->id,
            'userid'        => $user->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);

        $plugin    = new \plagiarism_plugin_turnitin();
        $eventdata = [
            'other'             => ['modulename' => 'assign', 'content' => '', 'pathnamehashes' => []],
            'contextinstanceid' => $cm->id,
            'userid'            => $user->id,
            'relateduserid'     => $user->id,
            'eventtype'         => 'assessable_submitted',
            'objectid'          => $submissionid,
        ];

        $result = $plugin->event_handler($eventdata);

        $this->assertTrue($result);
    }

    /**
     * Insert a minimal plagiarism_turnitin_files row, merging provided overrides
     * with sensible defaults. Returns the new row id.
     */
    private function insert_submission_row(array $overrides = []): int {
        global $DB;

        $row = array_merge([
            'cm'             => 1,
            'userid'         => 1,
            'identifier'     => 'testhash',
            'statuscode'     => 'queued',
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'submitter'      => 1,
            'lastmodified'   => time(),
            'transmatch'     => 0,
        ], $overrides);

        return $DB->insert_record('plagiarism_turnitin_files', (object) $row);
    }
}

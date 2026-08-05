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
        \plagiarism_plugin_turnitin::course_reset($eventdata);

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
        \plagiarism_plugin_turnitin::course_reset($eventdata);

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
}

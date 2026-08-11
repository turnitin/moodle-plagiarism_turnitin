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
 * Privacy provider tests.
 *
 * @package    plagiarism_turnitin
 * @copyright  2018 Turnitin
 * @author     David Winn <dwinn@turnitin.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use plagiarism_turnitin\privacy\provider;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;

require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/tests/lib_test.php');

if (!class_exists('\core_privacy\tests\provider_testcase')) {
    return;
}

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for plagiarsm/turnitin/privacy
 */
#[CoversClass(\plagiarism_turnitin\privacy\provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Test for _get_metadata shim.
     */
    public function test_get_metadata(): void {
        $this->resetAfterTest();

        $collection = new collection('plagiarism_turnitin');
        $newcollection = \plagiarism_turnitin\privacy\provider::_get_metadata($collection);
        $itemcollection = $newcollection->get_collection();

        $this->assertCount(4, $itemcollection);

        // Verify core_files data is returned.
        $this->assertEquals('core_files', $itemcollection[0]->get_name());
        $this->assertEquals('privacy:metadata:core_files', $itemcollection[0]->get_summary());

        // Verify plagiarism_turnitin_files data is returned.
        $this->assertEquals('plagiarism_turnitin_files', $itemcollection[1]->get_name());

        $privacyfields = $itemcollection[1]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('similarityscore', $privacyfields);
        $this->assertArrayHasKey('attempt', $privacyfields);
        $this->assertArrayHasKey('transmatch', $privacyfields);
        $this->assertArrayHasKey('lastmodified', $privacyfields);
        $this->assertArrayHasKey('lastmodified', $privacyfields);
        $this->assertArrayHasKey('grade', $privacyfields);
        $this->assertArrayHasKey('orcapable', $privacyfields);
        $this->assertArrayHasKey('student_read', $privacyfields);

        // Verify plagiarism_turnitin_user data is returned.
        $this->assertEquals('plagiarism_turnitin_users', $itemcollection[2]->get_name());

        $privacyfields = $itemcollection[2]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('turnitin_uid', $privacyfields);
        $this->assertArrayHasKey('instructor_defaults', $privacyfields);
        $this->assertArrayHasKey('instructor_rubrics', $privacyfields);
        $this->assertArrayHasKey('user_agreement_accepted', $privacyfields);

        // Verify plagiarism_turnitin_client data is returned.
        $this->assertEquals('plagiarism_turnitin_client', $itemcollection[3]->get_name());

        $privacyfields = $itemcollection[3]->get_privacy_fields();
        $this->assertArrayHasKey('email', $privacyfields);
        $this->assertArrayHasKey('firstname', $privacyfields);
        $this->assertArrayHasKey('lastname', $privacyfields);
        $this->assertArrayHasKey('submission_title', $privacyfields);
        $this->assertArrayHasKey('submission_filename', $privacyfields);
        $this->assertArrayHasKey('submission_content', $privacyfields);

        $this->assertEquals('privacy:metadata:plagiarism_turnitin_client', $itemcollection[3]->get_summary());
    }

    /**
     * Test that user's contexts are exported.
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        global $DB;

        $csresponse = $this->create_submission();

        $submissions = $DB->get_records('plagiarism_turnitin_files');

        $this->assertEquals(1, count($submissions));

        $contextlist = provider::get_contexts_for_userid($csresponse["Student"]->id);

        $this->assertCount(1, $contextlist);
    }

    /**
     * Test that all user data is exported.
     * @return void
     * @throws \dml_exception
     */
    public function test_export_plagiarism_user_data(): void {
        $this->resetAfterTest();
        global $DB;

        $csresponse = $this->create_submission();

        $submissions = $DB->get_records('plagiarism_turnitin_files');
        $this->assertEquals(1, count($submissions));

        // Export all of the data for the user.
        provider::export_plagiarism_user_data($csresponse["Student"]->id, $csresponse["Context"], [], []);
        $writer = \core_privacy\local\request\writer::with_context($csresponse["Context"]);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test that all user data is deleted.
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_plagiarism_for_user(): void {
        $this->resetAfterTest();
        global $DB;

        $csresponse = $this->create_submission();
        $csresponse2 = $this->create_submission();

        $submissions = $DB->get_records('plagiarism_turnitin_files');
        $this->assertEquals(2, count($submissions));

        // Delete all of the data for the user for the first submission.
        provider::delete_plagiarism_for_user($csresponse["Student"]->id, $csresponse["Context"]);

        $submissions = $DB->get_records('plagiarism_turnitin_files');
        $this->assertEquals(1, count($submissions));

        provider::delete_plagiarism_for_user($csresponse2["Student"]->id, $csresponse2["Context"]);
        $submissions = $DB->get_records('plagiarism_turnitin_files');
        $this->assertEquals(0, count($submissions));
    }

    /**
     * Test that all context data is deleted.
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_plagiarism_for_context(): void {
        $this->resetAfterTest();
        global $DB;

        $csresponse = $this->create_submission(3);

        $submissions = $DB->get_records('plagiarism_turnitin_files');
        $this->assertEquals(3, count($submissions));

        // Delete all of the data for the user.
        provider::delete_plagiarism_for_context($csresponse["Context"]);

        $submissions = $DB->get_records('plagiarism_turnitin_files');
        $this->assertEquals(0, count($submissions));
    }

    /**
     * Create a submission for testing.
     *
     * @param int $numsubmissions
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function create_submission($numsubmissions = 1) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

        // Use a static counter so each call to create_submission() gets a unique
        // base externalid, avoiding the unique index constraint on that column.
        static $externalidbase = 100000000;

        $libtest = new \plagiarism_turnitin\lib_test("create_submission");
        $result = $libtest->create_assign_with_student_and_teacher([
            'assignsubmission_onlinetext_enabled' => 1,
            'teamsubmission' => 0,
        ]);

        $assignmodule = $result['assign'];
        $student = $result['student'];
        $cm = get_coursemodule_from_instance('assign', $assignmodule->id);
        $context = \context_module::instance($cm->id);

        $plagiarismfile = new \stdClass();
        $plagiarismfile->cm = $cm->id;
        $plagiarismfile->userid = $student->id;
        $plagiarismfile->identifier = "abcd";
        $plagiarismfile->statuscode = "success";
        $plagiarismfile->similarityscore = 50;
        $plagiarismfile->attempt = 1;
        $plagiarismfile->transmatch = 0;
        $plagiarismfile->lastmodified = time();
        $plagiarismfile->submissiontype = 2;
        $plagiarismfile->itemid = 12;
        $plagiarismfile->submitter = $student->id;

        for ($i = 0; $i < $numsubmissions; $i++) {
            // Externalid must be unique per row due to a database unique index constraint.
            $plagiarismfile->externalid = $externalidbase++;
            $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile);
        }

        $this->setUser($student);

        return ["Student" => $student, "Context" => $context];
    }

    // Tests for uncovered privacy provider paths.

    /**
     * Test _export_plagiarism_user_data returns early when userid is empty.
     * Exercises line 143.
     */
    public function test_export_plagiarism_user_data_returns_early_when_userid_empty(): void {
        global $DB;
        $this->resetAfterTest();

        $csresponse = $this->create_submission(1);
        $countbefore = $DB->count_records('plagiarism_turnitin_files');

        // Passing userid=0 should hit the early return without exporting anything.
        provider::export_plagiarism_user_data(0, $csresponse['Context'], [], []);

        // No exception = pass; files count should be unchanged.
        $this->assertEquals($countbefore, $DB->count_records('plagiarism_turnitin_files'));
    }

    /**
     * Test _delete_plagiarism_for_context returns early when context is not a module context.
     * Exercises lines 203-207.
     */
    public function test_delete_plagiarism_for_context_returns_early_for_non_module_context(): void {
        global $DB;
        $this->resetAfterTest();

        $this->create_submission(1);
        $countbefore = $DB->count_records('plagiarism_turnitin_files');

        $coursecontext = \context_system::instance();
        provider::delete_plagiarism_for_context($coursecontext);

        // No rows should have been deleted.
        $this->assertEquals($countbefore, $DB->count_records('plagiarism_turnitin_files'));
    }

    /**
     * Test _delete_plagiarism_for_user returns early when context is not a module context.
     * Exercises line 225.
     */
    public function test_delete_plagiarism_for_user_returns_early_for_non_module_context(): void {
        global $DB;
        $this->resetAfterTest();

        $csresponse = $this->create_submission(1);
        $countbefore = $DB->count_records('plagiarism_turnitin_files');

        $systemcontext = \context_system::instance();
        provider::delete_plagiarism_for_user($csresponse['Student']->id, $systemcontext);

        $this->assertEquals($countbefore, $DB->count_records('plagiarism_turnitin_files'));
    }

    /**
     * Test get_users_in_context returns early when context is not a module context.
     * Exercises line 237.
     */
    public function test_get_users_in_context_returns_early_for_non_module_context(): void {
        $this->resetAfterTest();

        $systemcontext = \context_system::instance();
        $userlist = new \core_privacy\local\request\userlist($systemcontext, 'plagiarism_turnitin');

        provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * Test delete_data_for_users returns early when context is not a module context.
     * Exercises line 269.
     */
    public function test_delete_data_for_users_returns_early_for_non_module_context(): void {
        global $DB;
        $this->resetAfterTest();

        $this->create_submission(1);
        $countbefore = $DB->count_records('plagiarism_turnitin_files');

        $systemcontext = \context_system::instance();
        $userlist = new \core_privacy\local\request\approved_userlist($systemcontext, 'plagiarism_turnitin', [1]);

        provider::delete_data_for_users($userlist);

        $this->assertEquals($countbefore, $DB->count_records('plagiarism_turnitin_files'));
    }

    /**
     * Test delete_data_for_users deletes the correct rows for approved users within a module context.
     * Exercises lines 273-295 (the main delete path, after fixing the pts→ptf SQL bug).
     */
    public function test_delete_data_for_users_deletes_matching_rows(): void {
        global $DB;
        $this->resetAfterTest();

        $csresponse = $this->create_submission(2);
        $context    = $csresponse['Context'];
        $student    = $csresponse['Student'];

        $countbefore = $DB->count_records('plagiarism_turnitin_files', ['userid' => $student->id]);
        $this->assertGreaterThan(0, $countbefore);

        $userlist = new \core_privacy\local\request\approved_userlist($context, 'plagiarism_turnitin', [$student->id]);
        provider::delete_data_for_users($userlist);

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files', ['userid' => $student->id]));
    }
}

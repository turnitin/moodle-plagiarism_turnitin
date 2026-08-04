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
 * Unit tests for the static save methods on turnitin_submission.
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

/**
 * Tests for turnitin_submission::save and turnitin_submission::save_errored.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_submission::class)]
final class turnitin_submission_test extends \advanced_testcase {
    // Save_errored tests.

    /**
     * Test that save_errored sets statuscode to error, increments the attempt
     * counter, and records the errorcode on the existing row.
     */
    public function test_save_errored_updates_existing_row(): void {
        global $DB;
        $this->resetAfterTest();

        $id = $this->insert_submission_row(['statuscode' => 'queued', 'attempt' => 0]);

        turnitin_submission::save_errored($id, 0, 9);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('error', $row->statuscode);
        $this->assertEquals(1, $row->attempt);
        $this->assertEquals(9, $row->errorcode);
    }

    /**
     * Test that save_errored increments the attempt from whatever value it was,
     * not always from zero.
     */
    public function test_save_errored_increments_attempt_from_current_value(): void {
        global $DB;
        $this->resetAfterTest();

        $id = $this->insert_submission_row(['statuscode' => 'queued', 'attempt' => 2]);

        turnitin_submission::save_errored($id, 2, 14);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals(3, $row->attempt);
        $this->assertEquals(14, $row->errorcode);
    }

    // Save tests: insert path (submissionid = 0).

    /**
     * Test that save inserts a new row when submissionid is 0, and that all
     * fields are written correctly.
     */
    public function test_save_inserts_new_row_when_submission_id_is_zero(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files'));

        turnitin_submission::save($cm, $user->id, 0, 'abc123', 'success', 'tii-999', $user->id, 1, 'file', 0);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files'));

        $row = $DB->get_record('plagiarism_turnitin_files', ['cm' => $cm->id]);
        $this->assertEquals($user->id, $row->userid);
        $this->assertEquals('abc123', $row->identifier);
        $this->assertEquals('success', $row->statuscode);
        $this->assertEquals('tii-999', $row->externalid);
        $this->assertEquals(1, $row->attempt);
        $this->assertEquals('file', $row->submissiontype);
        $this->assertEquals(1, $row->itemid);
    }

    /**
     * Test that save does not set an id on the new row when inserting, so the
     * database assigns the primary key rather than using 0.
     */
    public function test_save_insert_does_not_use_zero_as_primary_key(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        turnitin_submission::save($cm, $user->id, 0, 'hash', 'queued', null, $user->id, 0, 'text_content', 0);

        $row = $DB->get_record('plagiarism_turnitin_files', ['cm' => $cm->id]);
        $this->assertGreaterThan(0, $row->id);
    }

    // Save tests: update path (submissionid != 0).

    /**
     * Test that save updates an existing row when a non-zero submissionid is given.
     */
    public function test_save_updates_existing_row_when_submission_id_is_nonzero(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row(['statuscode' => 'queued', 'attempt' => 0,
            'cm' => $cm->id, 'userid' => $user->id]);

        turnitin_submission::save($cm, $user->id, $id, 'newhash', 'success', 'tii-42', $user->id, 1, 'file', 0);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files'));

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('success', $row->statuscode);
        $this->assertEquals('tii-42', $row->externalid);
        $this->assertEquals('newhash', $row->identifier);
        $this->assertEquals(1, $row->attempt);
    }

    /**
     * Test that save stores optional errorcode and errormsg when provided,
     * and stores null when they are omitted.
     */
    public function test_save_stores_errorcode_and_errormsg_when_provided(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        turnitin_submission::save($cm, $user->id, 0, 'hash', 'error', null, $user->id, 0, 'file', 0, 7, 'some error');

        $row = $DB->get_record('plagiarism_turnitin_files', ['cm' => $cm->id]);
        $this->assertEquals(7, $row->errorcode);
        $this->assertEquals('some error', $row->errormsg);
    }

    /**
     * Test that save sets errorcode and errormsg to null when not provided,
     * clearing any previously stored values on update.
     */
    public function test_save_clears_errorcode_and_errormsg_when_not_provided(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row(['statuscode' => 'error', 'errorcode' => 9, 'attempt' => 1,
            'cm' => $cm->id, 'userid' => $user->id]);

        turnitin_submission::save($cm, $user->id, $id, 'hash', 'success', 'tii-1', $user->id, 0, 'file', 1);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertNull($row->errorcode);
        $this->assertNull($row->errormsg);
    }

    // Helpers.

    /**
     * Insert a minimal row into plagiarism_turnitin_files and return its id.
     *
     * @param array $overrides Field values to set, merged over sensible defaults.
     * @return int The new row id.
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

        return $DB->insert_record('plagiarism_turnitin_files', (object)$row);
    }
}

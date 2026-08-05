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
 * Unit tests for classes/turnitin_course.php.
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
 * Tests for turnitin_course.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_course::class)]
final class turnitin_course_test extends \advanced_testcase {
    /**
     * Build a minimal event stub for course_reset tests.
     *
     * @param int   $courseid
     * @param array $resetoptions Key-value pairs merged into reset_options.
     * @return \core\event\course_reset_ended
     */
    private function make_reset_event(int $courseid, array $resetoptions): \core\event\course_reset_ended {
        $event = $this->getMockBuilder(\core\event\course_reset_ended::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('get_data')->willReturn([
            'other' => ['reset_options' => array_merge(['courseid' => $courseid], $resetoptions)],
        ]);
        return $event;
    }

    /**
     * Insert a turnitin_assignid config row and a plagiarism_turnitin_files row for a CM.
     *
     * @param int $cmid
     * @param int $userid
     */
    private function insert_tii_submission(int $cmid, int $userid): void {
        global $DB;
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'turnitin_assignid',
            'value' => 99, 'config_hash' => $cmid . '_turnitin_assignid',
        ]);
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $cmid, 'userid' => $userid, 'identifier' => sha1((string)$cmid),
            'statuscode' => 'success', 'attempt' => 1, 'submissiontype' => 'file',
            'itemid' => 1, 'submitter' => $userid, 'lastmodified' => time(), 'transmatch' => 0,
        ]);
    }

    // Tests for course_reset().

    /**
     * Test course_reset deletes files and turnitin_assignid config when assign reset is requested.
     */
    public function test_course_reset_deletes_files_when_assign_reset(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_tii_submission($assign->cmid, $user->id);

        $event = $this->make_reset_event($course->id, ['reset_assign_submissions' => 1]);
        turnitin_course::course_reset($event);

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files', ['cm' => $assign->cmid]));
        $this->assertFalse($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $assign->cmid, 'name' => 'turnitin_assignid']
        ));
    }

    /**
     * Test course_reset preserves files when no reset flags are set.
     */
    public function test_course_reset_preserves_files_when_no_reset_flags(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_tii_submission($assign->cmid, $user->id);

        $event = $this->make_reset_event($course->id, []);
        turnitin_course::course_reset($event);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files', ['cm' => $assign->cmid]));
    }

    /**
     * Test course_reset removes the plagiarism_turnitin_courses row when all modules are reset.
     */
    public function test_course_reset_deletes_course_record_when_all_modules_reset(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_tii_submission($assign->cmid, $user->id);

        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid' => $course->id, 'turnitin_cid' => 42, 'turnitin_ctl' => 'Test Course',
        ]);

        $event = $this->make_reset_event($course->id, ['reset_assign_submissions' => 1]);
        turnitin_course::course_reset($event);

        $this->assertFalse($DB->record_exists('plagiarism_turnitin_courses', ['courseid' => $course->id]));
    }

    /**
     * Test course_reset preserves the course record when not all modules are reset.
     */
    public function test_course_reset_preserves_course_record_when_partial_reset(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_tii_submission($assign->cmid, $user->id);

        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid' => $course->id, 'turnitin_cid' => 42, 'turnitin_ctl' => 'Test Course',
        ]);

        // No reset flag set — assign submissions are preserved, so $resetcourse stays false.
        $event = $this->make_reset_event($course->id, []);
        turnitin_course::course_reset($event);

        $this->assertTrue($DB->record_exists('plagiarism_turnitin_courses', ['courseid' => $course->id]));
    }

    /**
     * Test course_reset returns true.
     */
    public function test_course_reset_returns_true(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $event  = $this->make_reset_event($course->id, []);

        $this->assertTrue(turnitin_course::course_reset($event));
    }

    /**
     * Test course_reset handles a course with no Turnitin-enabled modules gracefully.
     */
    public function test_course_reset_is_safe_with_no_turnitin_modules(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $event  = $this->make_reset_event($course->id, ['reset_assign_submissions' => 1]);

        // Should not throw when no turnitin_assignid config rows exist.
        turnitin_course::course_reset($event);
        $this->assertTrue(true);
    }

    // Tests for get_course_data().

    /**
     * Test get_course_data returns course data with turnitin_cid when one is already stored.
     */
    public function test_get_course_data_returns_existing_tii_cid(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid' => $course->id, 'turnitin_cid' => 77, 'turnitin_ctl' => 'My Course',
        ]);

        $coursedata = turnitin_course::get_course_data(0, $course->id);

        $this->assertEquals(77, $coursedata->turnitin_cid);
    }

    /**
     * Test get_course_data returns course data with turnitin_cid=0 when no stored CID
     * and plugin mock indicates no previous course or creation needed.
     *
     * When turnitin_cid is empty and get_previous_course_id returns false, create_tii_course
     * is called. We mock the plugin to return a coursedata with no CID (simulating a
     * connection failure), verifying the method still returns a usable object.
     */
    public function test_get_course_data_falls_back_to_create_when_no_cid_stored(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['create_tii_course', 'migrate_previous_course'])
            ->getMock();

        $mockcoursedata = (object)['turnitin_cid' => null, 'turnitin_ctl' => ''];
        $mock->method('create_tii_course')->willReturn($mockcoursedata);

        $coursedata = turnitin_course::get_course_data(0, $course->id, 'site', $mock);

        // No previous course and mock returns no CID — turnitin_cid is null.
        $this->assertNull($coursedata->turnitin_cid);
    }

    /**
     * Test get_course_data returns early without calling the plugin when cid is already stored.
     *
     * The plugin should not be called at all in the happy path.
     */
    public function test_get_course_data_does_not_call_plugin_when_cid_exists(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid' => $course->id, 'turnitin_cid' => 55, 'turnitin_ctl' => 'Course',
        ]);

        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['create_tii_course', 'migrate_previous_course'])
            ->getMock();

        $mock->expects($this->never())->method('create_tii_course');
        $mock->expects($this->never())->method('migrate_previous_course');

        turnitin_course::get_course_data(0, $course->id, 'site', $mock);
    }

    // Tests for get_previous_course_id().

    /**
     * Test get_previous_course_id returns false when no turnitin_assignid config row exists.
     */
    public function test_get_previous_course_id_returns_false_when_no_config(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        $result = turnitin_course::get_previous_course_id($assign->cmid, $course->id);

        $this->assertFalse($result);
    }

    /**
     * Test get_previous_course_id returns false when turnitin_assignid exists but no
     * matching record is found across course modules either.
     *
     * When a turnitin_assignid config row exists for the specific cm, the method calls
     * get_course_id_from_assignment_id() which makes an API call. In tests with no API
     * connection, this returns 0, so the method returns false.
     *
     * We test the fallback branch (no direct cm match) by using a different cm.
     */
    public function test_get_previous_course_id_returns_false_when_no_course_mods_have_assignid(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $assign  = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // No turnitin_assignid config for the specific cmid — triggers the fallback loop.
        // No config rows for any course module either — loop completes with tiicourseid=0.
        $result = turnitin_course::get_previous_course_id(0, $course->id);

        $this->assertFalse($result);
    }
}

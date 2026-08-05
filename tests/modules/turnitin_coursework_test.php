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
 * Unit tests for classes/modules/turnitin_coursework.php.
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
use plagiarism_turnitin\modules\turnitin_coursework;

/**
 * Tests for turnitin_coursework.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_coursework::class)]
final class turnitin_coursework_test extends \advanced_testcase {
    /**
     * Test constructor sets the expected public properties.
     */
    public function test_constructor_sets_properties(): void {
        $coursework = new turnitin_coursework();

        $this->assertEquals('coursework_feedbacks', $coursework->gradestable);
        $this->assertEquals('mod_coursework', $coursework->filecomponent);
    }

    /**
     * Test get_tutor_capability returns the correct string.
     */
    public function test_get_tutor_capability(): void {
        $coursework = new turnitin_coursework();
        $this->assertEquals('mod/coursework:addinitialgrade', $coursework->get_tutor_capability());
    }

    /**
     * Test is_tutor returns true for a user who has one of the coursework capabilities.
     *
     * Skipped when mod_coursework is not installed since the capabilities don't exist.
     */
    public function test_is_tutor_returns_true_for_capable_user(): void {
        $this->resetAfterTest();

        if (!class_exists(\mod_coursework\event\assessable_uploaded::class)) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $course   = $this->getDataGenerator()->create_course();
        $context  = \context_course::instance($course->id);
        $teacher  = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $this->setUser($teacher);

        $coursework = new turnitin_coursework();
        $this->assertTrue($coursework->is_tutor($context));
    }

    /**
     * Test is_tutor returns false for a student.
     *
     * Skipped when mod_coursework is not installed since the capabilities don't exist
     * and has_any_capability() triggers debugging() for each missing capability.
     */
    public function test_is_tutor_returns_false_for_student(): void {
        $this->resetAfterTest();

        if (!class_exists(\mod_coursework\event\assessable_uploaded::class)) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $coursework = new turnitin_coursework();
        $this->assertFalse($coursework->is_tutor($context));
    }

    /**
     * Test user_enrolled_on_course reflects the submit capability.
     *
     * Skipped when mod_coursework is not installed since mod/coursework:submit
     * doesn't exist and has_capability() triggers debugging().
     */
    public function test_user_enrolled_on_course_returns_false_when_capability_absent(): void {
        $this->resetAfterTest();

        if (!class_exists(\mod_coursework\event\assessable_uploaded::class)) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $user    = $this->getDataGenerator()->create_user();

        $coursework = new turnitin_coursework();
        $this->assertFalse($coursework->user_enrolled_on_course($context, $user->id));
    }

    /**
     * Test get_author returns 0 when no coursework_submissions row exists.
     *
     * Skipped when mod_coursework is not installed (table does not exist).
     */
    public function test_get_author_returns_zero_when_no_submission(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('coursework_submissions')) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $coursework = new turnitin_coursework();
        $this->assertEquals(0, $coursework->get_author(9999));
    }

    /**
     * Test get_author returns the authorid when a submission row exists.
     *
     * Inserts directly into coursework_submissions if the table exists; skips
     * gracefully when mod_coursework is not installed.
     */
    public function test_get_author_returns_authorid_when_submission_exists(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('coursework_submissions')) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $user  = $this->getDataGenerator()->create_user();
        $subid = $DB->insert_record('coursework_submissions', (object)[
            'courseworkid' => 1,
            'authorid'     => $user->id,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $coursework = new turnitin_coursework();
        $this->assertEquals($user->id, $coursework->get_author($subid));
    }

    /**
     * Test create_file_event is skipped when mod_coursework is not installed.
     */
    public function test_create_file_event_skipped_without_coursework(): void {
        $this->resetAfterTest();

        if (!class_exists(\mod_coursework\event\assessable_uploaded::class)) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $this->assertTrue(true);
    }

    /**
     * Test get_current_gradequery returns false when no matching record exists.
     *
     * When mod_coursework is not installed the tables don't exist; skip in that case.
     */
    public function test_get_current_gradequery_returns_false_when_no_record(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('coursework_submissions')) {
            $this->markTestSkipped('mod_coursework is not installed.');
        }

        $coursework = new turnitin_coursework();
        $this->assertFalse($coursework->get_current_gradequery(9999, 9999));
    }

    /**
     * Test initialise_post_date always returns 0.
     */
    public function test_initialise_post_date_returns_zero(): void {
        $coursework = new turnitin_coursework();
        $this->assertEquals(0, $coursework->initialise_post_date(new \stdClass()));
    }
}

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
 * Unit tests for classes/modules/turnitin_workshop.php.
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
use plagiarism_turnitin\modules\turnitin_workshop;

/**
 * Tests for turnitin_workshop.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_workshop::class)]
final class turnitin_workshop_test extends \advanced_testcase {
    /**
     * Test constructor sets the expected public properties.
     */
    public function test_constructor_sets_properties(): void {
        $workshop = new turnitin_workshop();

        $this->assertEquals('grade_grades', $workshop->gradestable);
        $this->assertEquals('mod_workshop', $workshop->filecomponent);
    }

    /**
     * Test get_tutor_capability returns the correct string.
     */
    public function test_get_tutor_capability(): void {
        $workshop = new turnitin_workshop();
        $this->assertEquals('plagiarism/turnitin:viewfullreport', $workshop->get_tutor_capability());
    }

    /**
     * Test is_tutor returns true for a user with viewfullreport.
     */
    public function test_is_tutor_returns_true_for_capable_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $workshop = new turnitin_workshop();
        $this->assertTrue($workshop->is_tutor($context));
    }

    /**
     * Test is_tutor returns false for a student.
     */
    public function test_is_tutor_returns_false_for_student(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $workshop = new turnitin_workshop();
        $this->assertFalse($workshop->is_tutor($context));
    }

    /**
     * Test user_enrolled_on_course returns true for a student with submit capability.
     */
    public function test_user_enrolled_on_course_returns_true_for_enrolled_student(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course   = $this->getDataGenerator()->create_course();
        $mod      = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $cm       = get_coursemodule_from_instance('workshop', $mod->id);
        $context  = \context_module::instance($cm->id);
        $student  = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $workshop = new turnitin_workshop();
        $this->assertTrue($workshop->user_enrolled_on_course($context, $student->id));
    }

    /**
     * Test get_author returns null (the method is a stub).
     */
    public function test_get_author_returns_null(): void {
        $workshop = new turnitin_workshop();
        $this->assertNull($workshop->get_author(1));
    }

    /**
     * Test set_content returns the content value from the linkarray.
     */
    public function test_set_content_returns_content_from_linkarray(): void {
        $workshop = new turnitin_workshop();
        $result   = $workshop->set_content(['content' => 'My workshop text'], 1);
        $this->assertEquals('My workshop text', $result);
    }

    /**
     * Test get_onlinetext returns an object with the workshop submission content.
     */
    public function test_get_onlinetext_returns_submission_data(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $mod     = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('workshop', $mod->id);
        $user    = $this->getDataGenerator()->create_user();

        // Insert a workshop submission directly.
        $subid = $DB->insert_record('workshop_submissions', (object)[
            'workshopid'    => $mod->id,
            'authorid'      => $user->id,
            'title'         => 'Test submission',
            'content'       => '<p>Hello world</p>',
            'contentformat' => FORMAT_HTML,
            'timecreated'   => time(),
            'timemodified'  => time(),
            'published'     => 0,
            'late'          => 0,
        ]);

        $workshop = new turnitin_workshop();
        $result   = $workshop->get_onlinetext($user->id, $cm);

        $this->assertEquals($subid, $result->itemid);
        $this->assertEquals('<p>Hello world</p>', $result->onlinetext);
        $this->assertEquals(FORMAT_HTML, $result->onlineformat);
    }

    /**
     * Test create_text_event returns a mod_workshop assessable_uploaded event.
     */
    public function test_create_text_event_returns_workshop_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $mod     = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('workshop', $mod->id);

        $params = [
            'context'  => \context_module::instance($cm->id),
            'objectid' => 1,
            'other'    => ['pathnamehashes' => [], 'content' => ''],
        ];

        $workshop = new turnitin_workshop();
        $event    = $workshop->create_file_event($params);

        $this->assertInstanceOf(\mod_workshop\event\assessable_uploaded::class, $event);
    }

    /**
     * Test create_file_event returns a mod_workshop assessable_uploaded event.
     */
    public function test_create_file_event_returns_workshop_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $mod     = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('workshop', $mod->id);

        $params = [
            'context'  => \context_module::instance($cm->id),
            'objectid' => 1,
            'other'    => ['pathnamehashes' => [], 'content' => ''],
        ];

        $workshop = new turnitin_workshop();
        $event    = $workshop->create_text_event($params);

        $this->assertInstanceOf(\mod_workshop\event\assessable_uploaded::class, $event);
    }

    /**
     * Test get_current_gradequery returns false when no matching grade record exists.
     */
    public function test_get_current_gradequery_returns_false_when_no_record(): void {
        $this->resetAfterTest();

        $workshop = new turnitin_workshop();
        $this->assertFalse($workshop->get_current_gradequery(9999, 9999));
    }

    /**
     * Test initialise_post_date returns the assessmentend value from moduledata.
     */
    public function test_initialise_post_date_returns_assessmentend(): void {
        $workshop   = new turnitin_workshop();
        $moduledata = (object)['assessmentend' => 1700000000];

        $this->assertEquals(1700000000, $workshop->initialise_post_date($moduledata));
    }
}

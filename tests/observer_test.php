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
 * Unit tests for classes/observer.php.
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
 * Tests for plagiarism_turnitin_observer.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(\plagiarism_turnitin_observer::class)]
final class observer_test extends \advanced_testcase {
    /**
     * Build a mock of plagiarism_plugin_turnitin that captures the $eventdata
     * passed to event_handler() so tests can assert on eventtype and modulename.
     *
     * @param array|null $captured Will be set to the $eventdata the mock received.
     * @return \plagiarism_plugin_turnitin
     */
    private function make_capturing_plugin(?array &$captured): \plagiarism_plugin_turnitin {
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['event_handler'])
            ->getMock();
        $mock->expects($this->once())
            ->method('event_handler')
            ->willReturnCallback(function (array $eventdata) use (&$captured) {
                $captured = $eventdata;
            });
        return $mock;
    }

    /**
     * Build a mock event whose get_data() returns the given array.
     *
     * Using a mock avoids the complex validation requirements of each concrete
     * event class while still satisfying the type hint.
     *
     * @param string $eventclass Fully-qualified event class name.
     * @param array $data Value for get_data() to return.
     * @return object
     */
    private function make_mock_event(string $eventclass, array $data): object {
        $mock = $this->getMockBuilder($eventclass)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_data'])
            ->getMock();
        $mock->method('get_data')->willReturn($data);
        return $mock;
    }

    /**
     * Base event data array shared across submission observer tests.
     * Each test may override 'other' to simulate real event payloads.
     */
    private function base_event_data(): array {
        return [
            'contextinstanceid' => 1,
            'userid'            => 2,
            'objectid'          => 3,
            'other'             => [],
        ];
    }

    // Tests for course_module_deleted.

    /**
     * Test that course_module_deleted removes the matching rows from both
     * plagiarism tables and leaves unrelated rows intact.
     */
    public function test_course_module_deleted_removes_matching_rows(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        // Insert a file record and a config record for the target cm.
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $cm->id, 'userid' => $user->id, 'submitter' => $user->id,
            'identifier' => sha1('a'), 'statuscode' => 'queued',
            'attempt' => 0, 'submissiontype' => 'file', 'itemid' => 0,
            'lastmodified' => time(), 'transmatch' => 0,
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // Insert records for a different cm that must NOT be deleted.
        $assign2 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm2     = get_coursemodule_from_instance('assign', $assign2->id);
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $cm2->id, 'userid' => $user->id, 'submitter' => $user->id,
            'identifier' => sha1('b'), 'statuscode' => 'queued',
            'attempt' => 0, 'submissiontype' => 'file', 'itemid' => 0,
            'lastmodified' => time(), 'transmatch' => 0,
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm2->id, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cm2->id . '_use_turnitin',
        ]);

        // Build the event mock so get_data() returns contextinstanceid = $cm->id.
        $event = $this->make_mock_event(
            \core\event\course_module_deleted::class,
            ['contextinstanceid' => $cm->id]
        );

        \plagiarism_turnitin_observer::course_module_deleted($event);

        // Target cm records must be gone.
        $this->assertFalse($DB->record_exists('plagiarism_turnitin_files', ['cm' => $cm->id]));
        $this->assertFalse($DB->record_exists('plagiarism_turnitin_config', ['cm' => $cm->id]));

        // Unrelated cm records must be untouched.
        $this->assertTrue($DB->record_exists('plagiarism_turnitin_files', ['cm' => $cm2->id]));
        $this->assertTrue($DB->record_exists('plagiarism_turnitin_config', ['cm' => $cm2->id]));
    }

    /**
     * Test that course_module_deleted is a no-op when there are no matching records.
     */
    public function test_course_module_deleted_is_safe_when_no_records_exist(): void {
        $this->resetAfterTest();

        $event = $this->make_mock_event(
            \core\event\course_module_deleted::class,
            ['contextinstanceid' => 9999]
        );

        // Should not throw.
        \plagiarism_turnitin_observer::course_module_deleted($event);
        $this->assertTrue(true);
    }

    // Tests for course_reset.

    /**
     * Test that course_reset runs without error when delegating to the plugin.
     *
     * plagiarism_plugin_turnitin::course_reset() is a static method and cannot
     * be mocked via PHPUnit's onlyMethods(). We verify the call path executes
     * safely with a minimal but structurally valid event — the static method
     * itself is covered by the existing lib_test.php suite.
     */
    public function test_course_reset_runs_without_error(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $eventdata = [
            'courseid' => $course->id,
            'other'    => [
                'reset_options' => ['courseid' => $course->id],
            ],
        ];
        $event = $this->make_mock_event(\core\event\course_reset_ended::class, $eventdata);

        // Should delegate to plagiarism_plugin_turnitin::course_reset() without throwing.
        \plagiarism_turnitin_observer::course_reset($event);
        $this->assertTrue(true);
    }

    // Tests for submission observer methods — each verifies that event_handler()
    // is called with the correct eventtype and modulename.

    /**
     * Test that assignsubmission_file_uploaded stamps eventtype=file_uploaded, modulename=assign.
     */
    public function test_assignsubmission_file_uploaded_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \assignsubmission_file\event\assessable_uploaded::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::assignsubmission_file_uploaded($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('file_uploaded', $captured['eventtype']);
        $this->assertEquals('assign', $captured['other']['modulename']);
    }

    /**
     * Test that forum_file_uploaded stamps eventtype=assessable_submitted, modulename=forum.
     */
    public function test_forum_file_uploaded_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \mod_forum\event\assessable_uploaded::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::forum_file_uploaded($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('assessable_submitted', $captured['eventtype']);
        $this->assertEquals('forum', $captured['other']['modulename']);
    }

    /**
     * Test that workshop_file_uploaded stamps eventtype=assessable_submitted, modulename=workshop.
     */
    public function test_workshop_file_uploaded_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \mod_workshop\event\assessable_uploaded::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::workshop_file_uploaded($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('assessable_submitted', $captured['eventtype']);
        $this->assertEquals('workshop', $captured['other']['modulename']);
    }

    /**
     * Test that assignsubmission_onlinetext_uploaded stamps eventtype=content_uploaded, modulename=assign.
     */
    public function test_assignsubmission_onlinetext_uploaded_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \assignsubmission_onlinetext\event\assessable_uploaded::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::assignsubmission_onlinetext_uploaded($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('content_uploaded', $captured['eventtype']);
        $this->assertEquals('assign', $captured['other']['modulename']);
    }

    /**
     * Test that coursework_submitted stamps eventtype=assessable_submitted, modulename=coursework.
     */
    public function test_coursework_submitted_stamps_correct_eventdata(): void {
        $this->resetAfterTest();

        if (!class_exists(\mod_coursework\event\assessable_uploaded::class)) {
            $this->markTestSkipped('mod_coursework is not installed in this environment.');
        }

        $captured = null;

        $event = $this->make_mock_event(
            \mod_coursework\event\assessable_uploaded::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::coursework_submitted($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('assessable_submitted', $captured['eventtype']);
        $this->assertEquals('coursework', $captured['other']['modulename']);
    }

    /**
     * Test that assignsubmission_submitted stamps eventtype=assessable_submitted, modulename=assign.
     */
    public function test_assignsubmission_submitted_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \mod_assign\event\assessable_submitted::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::assignsubmission_submitted($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('assessable_submitted', $captured['eventtype']);
        $this->assertEquals('assign', $captured['other']['modulename']);
    }

    /**
     * Test that assignsubmission_removed stamps eventtype=submission_removed, modulename=assign.
     */
    public function test_assignsubmission_removed_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \mod_assign\event\submission_removed::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::assignsubmission_removed($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('submission_removed', $captured['eventtype']);
        $this->assertEquals('assign', $captured['other']['modulename']);
    }

    /**
     * Test that quiz_submitted stamps eventtype=quiz_submitted, modulename=quiz.
     */
    public function test_quiz_submitted_stamps_correct_eventdata(): void {
        $this->resetAfterTest();
        $captured = null;

        $event = $this->make_mock_event(
            \mod_quiz\event\attempt_submitted::class,
            $this->base_event_data()
        );

        \plagiarism_turnitin_observer::quiz_submitted($event, $this->make_capturing_plugin($captured));

        $this->assertEquals('quiz_submitted', $captured['eventtype']);
        $this->assertEquals('quiz', $captured['other']['modulename']);
    }
}

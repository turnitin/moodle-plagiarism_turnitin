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
 * Unit tests for classes/digitalreceipt/pp_receipt_message.php.
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
use plagiarism_turnitin\digitalreceipt\pp_receipt_message;

/**
 * Tests for pp_receipt_message.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(pp_receipt_message::class)]
final class pp_receipt_message_test extends \advanced_testcase {
    // Build_message tests.

    /**
     * Test that build_message returns a non-empty string containing the
     * submission title passed in.
     */
    public function test_build_message_contains_submission_title(): void {
        $this->resetAfterTest();

        $receipt = new pp_receipt_message();
        $result  = $receipt->build_message($this->make_input(['submission_title' => 'My Essay']));

        $this->assertStringContainsString('My Essay', $result);
    }

    /**
     * Test that build_message includes the student's first and last name.
     */
    public function test_build_message_contains_student_name(): void {
        $this->resetAfterTest();

        $receipt = new pp_receipt_message();
        $result  = $receipt->build_message($this->make_input([
            'firstname' => 'Alice',
            'lastname'  => 'Smith',
        ]));

        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Smith', $result);
    }

    /**
     * Test that build_message includes the assignment name and course fullname.
     */
    public function test_build_message_contains_assignment_and_course(): void {
        $this->resetAfterTest();

        $receipt = new pp_receipt_message();
        $result  = $receipt->build_message($this->make_input([
            'assignment_name' => 'Week 3 Essay',
            'course_fullname' => 'Introduction to Philosophy',
        ]));

        $this->assertStringContainsString('Week 3 Essay', $result);
        $this->assertStringContainsString('Introduction to Philosophy', $result);
    }

    /**
     * Test that build_message includes the Turnitin submission id.
     */
    public function test_build_message_contains_submission_id(): void {
        $this->resetAfterTest();

        $receipt = new pp_receipt_message();
        $result  = $receipt->build_message($this->make_input(['submission_id' => 'tii-abc-999']));

        $this->assertStringContainsString('tii-abc-999', $result);
    }

    /**
     * Test that build_message appends the assignment_part string when provided.
     */
    public function test_build_message_appends_assignment_part_when_provided(): void {
        $this->resetAfterTest();

        $receipt   = new pp_receipt_message();
        $withpart  = $receipt->build_message($this->make_input(['assignment_part' => 'Part A']));
        $nopart    = $receipt->build_message($this->make_input());

        $this->assertStringContainsString('Part A', $withpart);
        $this->assertStringNotContainsString('Part A', $nopart);
    }

    // Build_instructor_message tests.

    /**
     * Test that build_instructor_message contains the submission title but not
     * the student's name, preserving submission anonymity.
     */
    public function test_build_instructor_message_contains_title_not_student_name(): void {
        $this->resetAfterTest();

        $receipt = new pp_receipt_message();
        $result  = $receipt->build_instructor_message($this->make_input([
            'submission_title' => 'Anonymous Essay',
            'firstname'        => 'Alice',
            'lastname'         => 'Smith',
        ]));

        $this->assertStringContainsString('Anonymous Essay', $result);
        // The instructor copy should not reveal the student's identity.
        $this->assertStringNotContainsString('Alice', $result);
        $this->assertStringNotContainsString('Smith', $result);
    }

    /**
     * Test that build_instructor_message includes submission id, assignment and course.
     */
    public function test_build_instructor_message_contains_submission_details(): void {
        $this->resetAfterTest();

        $receipt = new pp_receipt_message();
        $result  = $receipt->build_instructor_message($this->make_input([
            'assignment_name' => 'Final Project',
            'course_fullname' => 'Advanced Maths',
            'submission_id'   => 'tii-xyz-123',
        ]));

        $this->assertStringContainsString('Final Project', $result);
        $this->assertStringContainsString('Advanced Maths', $result);
        $this->assertStringContainsString('tii-xyz-123', $result);
    }

    // Send_message tests.

    /**
     * Test that send_message delivers a message to the student's inbox.
     */
    public function test_send_message_delivers_to_student(): void {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();

        $sink   = $this->redirectMessages();
        $receipt = new pp_receipt_message();
        $receipt->send_message($student->id, '<p>Your submission was received.</p>', $course->id);

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals($student->id, $messages[0]->useridto);
        $this->assertStringContainsString('Your submission was received.', $messages[0]->fullmessage);
    }

    /**
     * Test that send_message uses the plagiarism_turnitin component and
     * submission message name so routing rules apply correctly.
     */
    public function test_send_message_uses_correct_component_and_name(): void {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();

        $sink    = $this->redirectMessages();
        $receipt = new pp_receipt_message();
        $receipt->send_message($student->id, 'test body', $course->id);

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertEquals('plagiarism_turnitin', $messages[0]->component);
        $this->assertEquals('submission', $messages[0]->eventtype);
    }

    // Send_instructor_message tests.

    /**
     * Test that send_instructor_message delivers a copy to every instructor
     * in the provided list.
     */
    public function test_send_instructor_message_delivers_to_all_instructors(): void {
        $this->resetAfterTest();

        $instructor1 = $this->getDataGenerator()->create_user();
        $instructor2 = $this->getDataGenerator()->create_user();

        $sink    = $this->redirectMessages();
        $receipt = new pp_receipt_message();
        $receipt->send_instructor_message([$instructor1, $instructor2], '<p>A submission was made.</p>');

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(2, $messages);

        $recipients = array_column($messages, 'useridto');
        $this->assertContains((string)$instructor1->id, $recipients);
        $this->assertContains((string)$instructor2->id, $recipients);
    }

    /**
     * Test that send_instructor_message sends nothing when the instructor list
     * is empty.
     */
    public function test_send_instructor_message_sends_nothing_for_empty_list(): void {
        $this->resetAfterTest();

        $sink    = $this->redirectMessages();
        $receipt = new pp_receipt_message();
        $receipt->send_instructor_message([], 'nobody home');

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(0, $messages);
    }

    // Helpers.

    /**
     * Build a minimal input array for build_message / build_instructor_message,
     * with sensible defaults that can be overridden per test.
     *
     * @param array $overrides
     * @return array
     */
    private function make_input(array $overrides = []): array {
        return array_merge([
            'firstname'       => 'Test',
            'lastname'        => 'Student',
            'submission_title' => 'Test Submission',
            'assignment_name' => 'Test Assignment',
            'course_fullname' => 'Test Course',
            'submission_date' => '01-Jan-2026 12:00PM',
            'submission_id'   => 'tii-test-001',
        ], $overrides);
    }
}

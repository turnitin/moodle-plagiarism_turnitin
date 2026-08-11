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
 * Unit tests for classes/turnitin_ajax_handler.php.
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
 * Tests for turnitin_ajax_handler.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_ajax_handler::class)]
final class turnitin_ajax_handler_test extends \advanced_testcase {
    // Tests for action_user_agreement().

    /**
     * Insert a plagiarism_turnitin_users row and return the row id.
     */
    private function insert_tii_user(int $userid, int $accepted = 0): int {
        global $DB;
        return $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                  => $userid,
            'turnitin_uid'            => 100,
            'turnitin_utp'            => 0,
            'user_agreement_accepted' => $accepted,
        ]);
    }

    /**
     * Test action_user_agreement sets user_agreement_accepted=1 when EULA is accepted.
     */
    public function test_action_user_agreement_sets_accepted_on_accept(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->insert_tii_user($user->id);

        turnitin_ajax_handler::action_user_agreement($user->id, 'turnitin_eula_accepted');

        $this->assertEquals(1, $DB->get_field(
            'plagiarism_turnitin_users',
            'user_agreement_accepted',
            ['userid' => $user->id]
        ));
    }

    /**
     * Test action_user_agreement sets user_agreement_accepted=-1 when EULA is declined.
     */
    public function test_action_user_agreement_sets_minus_one_on_decline(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->insert_tii_user($user->id);

        turnitin_ajax_handler::action_user_agreement($user->id, 'turnitin_eula_declined');

        $this->assertEquals(-1, $DB->get_field(
            'plagiarism_turnitin_users',
            'user_agreement_accepted',
            ['userid' => $user->id]
        ));
    }

    /**
     * Test action_user_agreement leaves user_agreement_accepted=0 for unknown message.
     */
    public function test_action_user_agreement_sets_zero_for_unknown_message(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->insert_tii_user($user->id, 1);

        turnitin_ajax_handler::action_user_agreement($user->id, 'unknown_message');

        $this->assertEquals(0, $DB->get_field(
            'plagiarism_turnitin_users',
            'user_agreement_accepted',
            ['userid' => $user->id]
        ));
    }

    // Tests for record_grade_sync_timestamp().

    /**
     * Test record_grade_sync_timestamp inserts a new row when none exists.
     */
    public function test_record_grade_sync_timestamp_inserts_when_no_row(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $before = time();
        turnitin_ajax_handler::record_grade_sync_timestamp($cm->id);
        $after = time();

        $value = $DB->get_field(
            'plagiarism_turnitin_config',
            'value',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        );

        $this->assertGreaterThanOrEqual($before, (int)$value);
        $this->assertLessThanOrEqual($after, (int)$value);
    }

    /**
     * Test record_grade_sync_timestamp updates an existing row.
     */
    public function test_record_grade_sync_timestamp_updates_existing_row(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'grades_last_synced',
            'value'       => 1000,
            'config_hash' => $cm->id . '_grades_last_synced',
        ]);

        turnitin_ajax_handler::record_grade_sync_timestamp($cm->id);

        $count = $DB->count_records(
            'plagiarism_turnitin_config',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        );
        $this->assertEquals(1, $count);

        $value = $DB->get_field(
            'plagiarism_turnitin_config',
            'value',
            ['cm' => $cm->id, 'name' => 'grades_last_synced']
        );
        $this->assertGreaterThan(1000, (int)$value);
    }

    // Tests for resubmit_event().

    /**
     * Test resubmit_event returns true when recreate_submission_event succeeds.
     */
    public function test_resubmit_event_returns_true_on_success(): void {
        $this->resetAfterTest();

        $mocksubmission = $this->getMockBuilder(turnitin_submission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['recreate_submission_event'])
            ->getMock();
        $mocksubmission->method('recreate_submission_event')->willReturn(true);

        $result = turnitin_ajax_handler::resubmit_event(1, '', '', $mocksubmission);

        $this->assertTrue($result);
    }

    /**
     * Test resubmit_event returns false when recreate_submission_event fails.
     */
    public function test_resubmit_event_returns_false_on_failure(): void {
        $this->resetAfterTest();

        $mocksubmission = $this->getMockBuilder(turnitin_submission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['recreate_submission_event'])
            ->getMock();
        $mocksubmission->method('recreate_submission_event')->willReturn(false);

        $result = turnitin_ajax_handler::resubmit_event(1, '', '', $mocksubmission);

        $this->assertFalse($result);
    }

    // Tests for resubmit_events().

    /**
     * Test resubmit_events returns success=true when all submissions succeed.
     */
    public function test_resubmit_events_returns_success_when_all_succeed(): void {
        $this->resetAfterTest();

        $factory = function (int $id): turnitin_submission {
            $mock = $this->getMockBuilder(turnitin_submission::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['recreate_submission_event'])
                ->getMock();
            $mock->method('recreate_submission_event')->willReturn(true);
            return $mock;
        };

        $result = turnitin_ajax_handler::resubmit_events([1, 2, 3], $factory);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['errors']);
    }

    /**
     * Test resubmit_events returns success=false and lists failing ids when some fail.
     */
    public function test_resubmit_events_returns_failure_and_error_ids(): void {
        $this->resetAfterTest();

        $factory = function (int $id): turnitin_submission {
            $mock = $this->getMockBuilder(turnitin_submission::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['recreate_submission_event'])
                ->getMock();
            $mock->method('recreate_submission_event')->willReturn($id !== 2);
            return $mock;
        };

        $result = turnitin_ajax_handler::resubmit_events([1, 2, 3], $factory);

        $this->assertFalse($result['success']);
        $this->assertContains(2, $result['errors']);
        $this->assertNotContains(1, $result['errors']);
    }

    /**
     * Test resubmit_events returns success=true with empty errors for an empty list.
     */
    public function test_resubmit_events_returns_success_for_empty_list(): void {
        $this->resetAfterTest();

        $result = turnitin_ajax_handler::resubmit_events([]);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['errors']);
    }

    // Tests for test_connection().

    /**
     * Test test_connection returns success when the mock API call succeeds.
     */
    public function test_test_connection_returns_success_on_api_success(): void {
        $this->resetAfterTest();

        $mockapi = $this->getMockBuilder(\Integrations\PhpSdk\TurnitinAPI::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockapi->method('findClasses')->willReturn(new \stdClass());

        $mockcomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialise_api', 'set_diagnostic'])
            ->getMock();
        $mockcomms->method('initialise_api')->willReturn($mockapi);

        $result = turnitin_ajax_handler::test_connection('id', 'secret', 'https://api.turnitin.com', $mockcomms);

        $this->assertEquals(200, $result['connection_status']);
        $this->assertEquals(get_string('connecttestsuccess', 'plagiarism_turnitin'), $result['msg']);
    }

    /**
     * Test test_connection returns fail when the mock API call throws.
     */
    public function test_test_connection_returns_fail_on_api_exception(): void {
        $this->resetAfterTest();

        $mockapi = $this->getMockBuilder(\Integrations\PhpSdk\TurnitinAPI::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockapi->method('findClasses')->willThrowException(new \Exception('Connection failed'));

        $mockcomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialise_api', 'set_diagnostic', 'handle_exceptions'])
            ->getMock();
        $mockcomms->method('initialise_api')->willReturn($mockapi);
        $mockcomms->method('handle_exceptions')->willReturn(null);

        $result = turnitin_ajax_handler::test_connection('id', 'secret', 'https://api.turnitin.com', $mockcomms);

        $this->assertEquals('fail', $result['connection_status']);
    }
}

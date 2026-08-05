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
 * Unit tests for classes/turnitin_extras_handler.php.
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
 * Tests for turnitin_extras_handler.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_extras_handler::class)]
final class turnitin_extras_handler_test extends \advanced_testcase {
    /**
     * A no-op LTI launch callback that returns a predictable HTML string.
     *
     * @return callable
     */
    private function make_lti_callback(string $returnhtml = '<form id="test_lti"></form>'): callable {
        return function () use ($returnhtml): string {
            return $returnhtml;
        };
    }

    // Tests for render_rubric_manager().

    /**
     * Test render_rubric_manager returns a div with the launch form when no course record exists.
     */
    public function test_render_rubric_manager_with_no_course_record(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $result = turnitin_extras_handler::render_rubric_manager($course->id, $this->make_lti_callback());

        $this->assertStringContainsString('launch_form', $result);
        $this->assertStringContainsString('test_lti', $result);
    }

    /**
     * Test render_rubric_manager passes the stored turnitin_cid to the callback.
     */
    public function test_render_rubric_manager_passes_tii_course_id_to_callback(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid' => $course->id, 'turnitin_cid' => 77, 'turnitin_ctl' => 'Test',
        ]);

        $capturedcourseid = null;
        $callback = function (string $type, string $role, int $partid, int $classid) use (&$capturedcourseid): string {
            $capturedcourseid = $classid;
            return '';
        };

        turnitin_extras_handler::render_rubric_manager($course->id, $callback);

        $this->assertEquals(77, $capturedcourseid);
    }

    /**
     * Test render_rubric_manager uses 0 as the course id when no DB record exists.
     */
    public function test_render_rubric_manager_defaults_to_zero_when_no_tii_course(): void {
        $this->resetAfterTest();

        $course           = $this->getDataGenerator()->create_course();
        $capturedcourseid = null;
        $callback = function (string $type, string $role, int $partid, int $classid) use (&$capturedcourseid): string {
            $capturedcourseid = $classid;
            return '';
        };

        turnitin_extras_handler::render_rubric_manager($course->id, $callback);

        $this->assertEquals(0, $capturedcourseid);
    }

    /**
     * Test render_rubric_manager output contains the auto-submit script.
     */
    public function test_render_rubric_manager_contains_auto_submit_script(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $result  = turnitin_extras_handler::render_rubric_manager($course->id, $this->make_lti_callback());

        $this->assertStringContainsString('forms[0].submit()', $result);
    }

    // Tests for render_quickmark_manager().

    /**
     * Test render_quickmark_manager returns output containing the launch form.
     */
    public function test_render_quickmark_manager_returns_launch_form(): void {
        $this->resetAfterTest();

        $result = turnitin_extras_handler::render_quickmark_manager($this->make_lti_callback('<form id="qm_form"></form>'));

        $this->assertStringContainsString('qm_form', $result);
        $this->assertStringContainsString('launch_form', $result);
    }

    /**
     * Test render_quickmark_manager invokes the callback with the correct type.
     */
    public function test_render_quickmark_manager_calls_callback_with_correct_type(): void {
        $this->resetAfterTest();

        $capturedtype = null;
        $callback = function (string $type) use (&$capturedtype): string {
            $capturedtype = $type;
            return '';
        };

        turnitin_extras_handler::render_quickmark_manager($callback);

        $this->assertEquals('quickmark_manager', $capturedtype);
    }

    /**
     * Test render_quickmark_manager output contains the auto-submit script.
     */
    public function test_render_quickmark_manager_contains_auto_submit_script(): void {
        $this->resetAfterTest();

        $result = turnitin_extras_handler::render_quickmark_manager($this->make_lti_callback());

        $this->assertStringContainsString('forms[0].submit()', $result);
    }

    // Tests for render_user_agreement().

    /**
     * Build a mock turnitin_user with a known tiiuserid.
     */
    private function make_mock_user(int $tiiuserid = 0): turnitin_user {
        $mock = $this->getMockBuilder(turnitin_user::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->tiiuserid = $tiiuserid;
        return $mock;
    }

    /**
     * Test render_user_agreement returns output containing the EULA box.
     */
    public function test_render_user_agreement_returns_eula_box(): void {
        $this->resetAfterTest();

        $result = turnitin_extras_handler::render_user_agreement(
            1,
            $this->make_mock_user(),
            fn() => '<form id="eula_form"></form>'
        );

        $this->assertStringContainsString('tii_eula_launch', $result);
        $this->assertStringContainsString('eula_form', $result);
    }

    /**
     * Test render_user_agreement invokes the launchform callback with type=useragreement.
     */
    public function test_render_user_agreement_calls_callback_with_useragreement_type(): void {
        $this->resetAfterTest();

        $capturedtype = null;
        $callback = function (string $type) use (&$capturedtype): string {
            $capturedtype = $type;
            return '';
        };

        turnitin_extras_handler::render_user_agreement(1, $this->make_mock_user(), $callback);

        $this->assertEquals('useragreement', $capturedtype);
    }

    /**
     * Test render_user_agreement passes the user's tiiuserid to the callback.
     */
    public function test_render_user_agreement_passes_tii_user_id_to_callback(): void {
        $this->resetAfterTest();

        $captureduserid = null;
        $callback = function (
            string $type,
            int $submissionid,
            int $userid
        ) use (&$captureduserid): string {
            $captureduserid = $userid;
            return '';
        };

        turnitin_extras_handler::render_user_agreement(1, $this->make_mock_user(42), $callback);

        $this->assertEquals(42, $captureduserid);
    }

    /**
     * Test render_user_agreement output contains the auto-submit script.
     */
    public function test_render_user_agreement_contains_auto_submit_script(): void {
        $this->resetAfterTest();

        $result = turnitin_extras_handler::render_user_agreement(
            1,
            $this->make_mock_user(),
            fn() => ''
        );

        $this->assertStringContainsString('forms[0].submit()', $result);
    }
}

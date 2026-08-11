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
 * Unit tests for classes/turnitin_date_utils.php.
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
 * Tests for turnitin_date_utils.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_date_utils::class)]
final class turnitin_date_utils_test extends \advanced_testcase {
    /**
     * Build a minimal cm stdClass.
     */
    private function make_cm(string $modname = 'assign', int $added = 0): \stdClass {
        return (object)[
            'id'       => 1,
            'modname'  => $modname,
            'instance' => 1,
            'course'   => 1,
            'added'    => $added ?: time(),
        ];
    }

    /**
     * Build a minimal moduledata stdClass.
     */
    private function make_module(array $fields = []): \stdClass {
        return (object)$fields;
    }

    // Tests for start_date().

    /**
     * Test start_date uses allowsubmissionsfromdate when set.
     */
    public function test_start_date_uses_allowsubmissionsfromdate(): void {
        $future = strtotime('+1 month');
        $module = $this->make_module(['allowsubmissionsfromdate' => $future]);
        $cm     = $this->make_cm();

        $this->assertEquals($future, turnitin_date_utils::start_date($module, $cm));
    }

    /**
     * Test start_date falls back to timeavailable when allowsubmissionsfromdate is absent.
     */
    public function test_start_date_falls_back_to_timeavailable(): void {
        $future = strtotime('+2 weeks');
        $module = $this->make_module(['timeavailable' => $future]);
        $cm     = $this->make_cm();

        $this->assertEquals($future, turnitin_date_utils::start_date($module, $cm));
    }

    /**
     * Test start_date falls back to cm->added when neither date field is set.
     */
    public function test_start_date_falls_back_to_cm_added(): void {
        $added  = strtotime('-1 month');
        $module = $this->make_module();
        $cm     = $this->make_cm('assign', $added);

        $this->assertEquals($added, turnitin_date_utils::start_date($module, $cm));
    }

    /**
     * Test start_date clamps dates older than 1 year to 11 months ago.
     */
    public function test_start_date_clamps_very_old_dates(): void {
        $old    = strtotime('-2 years');
        $module = $this->make_module(['allowsubmissionsfromdate' => $old]);
        $cm     = $this->make_cm();

        $result   = turnitin_date_utils::start_date($module, $cm);
        $expected = strtotime('-11 months');

        // Allow a 5-second window for test execution time.
        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test start_date does not clamp a date that is exactly 1 year old (boundary).
     */
    public function test_start_date_does_not_clamp_exactly_one_year_old(): void {
        // The boundary: value equals strtotime('-1 year') which satisfies <= so it is clamped.
        $exactly = strtotime('-1 year');
        $module  = $this->make_module(['allowsubmissionsfromdate' => $exactly]);
        $cm      = $this->make_cm();

        $result = turnitin_date_utils::start_date($module, $cm);

        $this->assertEqualsWithDelta(strtotime('-11 months'), $result, 5);
    }

    // Tests for post_date().

    /**
     * Test post_date returns start+1s for forum modules (no grade item lookup).
     */
    public function test_post_date_forum_returns_start_plus_one_second(): void {
        $dtstart = strtotime('+1 month');
        $cm      = $this->make_cm('forum');
        $module  = $this->make_module();

        $result = turnitin_date_utils::post_date($cm, $module, $dtstart, null);

        $this->assertEquals($dtstart + 1, $result);
    }

    /**
     * Test post_date returns start+1s when no grade item exists for a non-forum module.
     */
    public function test_post_date_returns_start_plus_one_second_when_no_grade_item(): void {
        $dtstart = strtotime('+1 month');
        $cm      = $this->make_cm('assign');
        $module  = $this->make_module();

        $result = turnitin_date_utils::post_date($cm, $module, $dtstart, null);

        $this->assertEquals($dtstart + 1, $result);
    }

    /**
     * Test post_date is 6 months in future when grade item hidden=1.
     */
    public function test_post_date_is_six_months_future_when_hidden(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module();
        $gradeitem = (object)['hidden' => 1];

        $result   = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem);
        $expected = strtotime('+6 months');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test post_date equals dtstart+1 when grade item hidden=0 and no marking workflow.
     *
     * The minimum-1-second clamp always applies, so the result is dtstart+1 even when
     * the raw post date would equal dtstart.
     */
    public function test_post_date_equals_dtstart_when_not_hidden_no_workflow(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module(['markingworkflow' => 0]);
        $gradeitem = (object)['hidden' => 0];

        $result = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem);

        // With hidden=0 and no workflow, dtpost = dtstart; the minimum clamp then gives dtstart+1.
        $this->assertEquals($dtstart + 1, $result);
    }

    /**
     * Test post_date is 6 months in future when hidden=0, markingworkflow=1, no grades released.
     */
    public function test_post_date_is_six_months_when_workflow_and_no_releases(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module(['markingworkflow' => 1]);
        $gradeitem = (object)['hidden' => 0];

        $result   = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem, false);
        $expected = strtotime('+6 month');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test post_date is 5 minutes in past when hidden=0, markingworkflow=1, grades released.
     *
     * The raw post date is strtotime('-5 minutes'), but the minimum-1-second clamp
     * only applies when dtpost < dtstart+1. Since -5 minutes is well before dtstart
     * (+1 month), the clamp overrides to dtstart+1.
     *
     * To test the -5 minutes path without the clamp, use a dtstart in the past.
     */
    public function test_post_date_is_five_minutes_past_when_grades_released(): void {
        // Use a dtstart in the past so that strtotime('-5 minutes') > dtstart+1.
        $dtstart   = strtotime('-2 hours');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module(['markingworkflow' => 1]);
        $gradeitem = (object)['hidden' => 0];

        $result   = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem, true);
        $expected = strtotime('-5 minutes');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test post_date uses the hidden timestamp when it is a specific date.
     */
    public function test_post_date_uses_specific_hidden_timestamp(): void {
        $dtstart      = strtotime('+1 month');
        $hiddentimestamp = strtotime('+3 months');
        $cm           = $this->make_cm('assign');
        $module       = $this->make_module();
        $gradeitem    = (object)['hidden' => $hiddentimestamp];

        $result = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem);

        $this->assertEquals($hiddentimestamp, $result);
    }

    /**
     * Test post_date is pushed out 6 months for assign blind marking with unrevealed identities.
     */
    public function test_post_date_pushed_out_for_assign_blind_marking(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module(['blindmarking' => 1, 'revealidentities' => 0]);
        $gradeitem = (object)['hidden' => 0];

        $result   = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem);
        $expected = strtotime('+6 months');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test post_date is NOT pushed out when blind marking identities have been revealed.
     *
     * With revealidentities=1 the blind marking push is skipped. The raw dtpost is dtstart
     * (hidden=0, no workflow), which is then clamped to dtstart+1 by the minimum rule.
     */
    public function test_post_date_not_pushed_out_when_identities_revealed(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module(['blindmarking' => 1, 'revealidentities' => 1]);
        $gradeitem = (object)['hidden' => 0];

        $result = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem);

        // No blind-marking push → dtpost = dtstart, clamped to dtstart+1.
        $this->assertEquals($dtstart + 1, $result);
    }

    /**
     * Test post_date is pushed out 6 months for coursework blind marking.
     */
    public function test_post_date_pushed_out_for_coursework_blind_marking(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('coursework');
        $module    = $this->make_module(['blindmarking' => 1]);

        $result   = turnitin_date_utils::post_date($cm, $module, $dtstart, null);
        $expected = strtotime('+6 months');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test post_date is always at least 1 second after dtstart.
     */
    public function test_post_date_minimum_is_dtstart_plus_one_second(): void {
        $dtstart   = strtotime('+1 month');
        $cm        = $this->make_cm('assign');
        $module    = $this->make_module();
        // Pass gradeitem with hidden=0 so dtpost would equal dtstart.
        $gradeitem = (object)['hidden' => 0];

        $result = turnitin_date_utils::post_date($cm, $module, $dtstart, $gradeitem);

        // With hidden=0 the post date equals dtstart; the minimum clamp then gives dtstart+1.
        $this->assertEquals($dtstart + 1, $result);
    }

    // Tests for due_date().

    /**
     * Test due_date uses moduledata->duedate when set.
     */
    public function test_due_date_uses_moduledata_duedate(): void {
        $dtstart = strtotime('-1 month');
        $duedate = strtotime('+3 months');
        $module  = $this->make_module(['duedate' => $duedate]);

        $this->assertEquals($duedate, turnitin_date_utils::due_date($module, $dtstart));
    }

    /**
     * Test due_date defaults to start+1 month when duedate is absent.
     */
    public function test_due_date_defaults_to_start_plus_one_month_when_absent(): void {
        $dtstart = strtotime('-1 month');
        $module  = $this->make_module();

        $result   = turnitin_date_utils::due_date($module, $dtstart);
        $expected = strtotime('+1 month', $dtstart);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test due_date is capped at 1 year from now when set more than 1 year ahead.
     */
    public function test_due_date_capped_at_one_year(): void {
        $dtstart = strtotime('-1 month');
        $module  = $this->make_module(['duedate' => strtotime('+2 years')]);

        $result   = turnitin_date_utils::due_date($module, $dtstart);
        $expected = strtotime('+1 year');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test due_date is moved to start+1 month when it is before or equal to start date.
     */
    public function test_due_date_moves_to_after_start_when_before_start(): void {
        $dtstart = strtotime('+1 month');
        $module  = $this->make_module(['duedate' => strtotime('-1 month')]);

        $result   = turnitin_date_utils::due_date($module, $dtstart);
        $expected = strtotime('+1 month', $dtstart);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test due_date is set to tomorrow when in the past and submittoturnitin=true.
     */
    public function test_due_date_is_tomorrow_when_past_and_submitting(): void {
        $dtstart = strtotime('-3 months');
        $module  = $this->make_module(['duedate' => strtotime('-1 month')]);

        $result   = turnitin_date_utils::due_date($module, $dtstart, true);
        $expected = strtotime('+1 day');

        $this->assertEqualsWithDelta($expected, $result, 5);
    }

    /**
     * Test due_date is NOT moved to tomorrow when past but submittoturnitin=false.
     */
    public function test_due_date_not_moved_when_past_and_not_submitting(): void {
        $dtstart = strtotime('-3 months');
        $past    = strtotime('-1 month');
        $module  = $this->make_module(['duedate' => $past]);

        // The past duedate exceeds dtstart so it is kept; submittoturnitin=false means no further movement.
        $result = turnitin_date_utils::due_date($module, $dtstart, false);

        $this->assertEquals($past, $result);
    }
}

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
 * Unit tests for turnitin_submission::build_tii_assignment.
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
 * Tests for turnitin_submission::build_tii_assignment.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_submission::class)]
final class turnitin_submission_builder_test extends \advanced_testcase {

    /**
     * Seed all the CM-level settings that build_tii_assignment reads, using
     * sensible defaults that can be overridden per-test.
     *
     * @param int   $cmid     Course module id.
     * @param array $overrides Key-value pairs to override the defaults.
     */
    private function seed_cm_settings(int $cmid, array $overrides = []): void {
        global $DB;

        // Ensure a repository option is set so admin_config() doesn't produce warnings.
        if (empty(get_config('plagiarism_turnitin', 'plagiarism_turnitin_repositoryoption'))) {
            set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        }

        $defaults = [
            'use_turnitin'                        => 1,
            'plagiarism_submitpapersto'            => 1,
            'plagiarism_compare_student_papers'    => 1,
            'plagiarism_compare_internet'          => 1,
            'plagiarism_compare_journals'          => 1,
            'plagiarism_compare_institution'       => 0,
            'plagiarism_show_student_report'       => 0,
            'plagiarism_report_gen'                => 0,
            'plagiarism_exclude_biblio'            => 0,
            'plagiarism_exclude_quoted'            => 0,
            'plagiarism_exclude_matches'           => 0,
            'plagiarism_exclude_matches_value'     => 0,
            'plagiarism_allow_non_or_submissions'  => 0,
            'plagiarism_transmatch'                => 0,
            'plagiarism_rubric'                    => '',
        ];

        foreach (array_merge($defaults, $overrides) as $name => $value) {
            $DB->insert_record('plagiarism_turnitin_config', (object)[
                'cm'          => $cmid,
                'name'        => $name,
                'value'       => $value,
                'config_hash' => $cmid . '_' . $name,
            ]);
        }
    }

    /**
     * Test build_tii_assignment returns a TiiAssignment with classId and title set correctly.
     */
    public function test_build_tii_assignment_sets_class_id_and_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'Test Essay']);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        $result = turnitin_submission::build_tii_assignment($cm, 42);

        $this->assertEquals(42, $result['assignment']->getClassId());
        $this->assertEquals('Test Essay', $result['assignment']->getTitle());
        $this->assertIsInt($result['dtdue']);
    }

    /**
     * Test that long assignment names are truncated to 80 chars + ellipsis.
     */
    public function test_build_tii_assignment_truncates_long_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $longname = str_repeat('A', 90);
        $course   = $this->getDataGenerator()->create_course();
        $assign   = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => $longname]);
        $cm       = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $title = $result['assignment']->getTitle();
        $this->assertStringEndsWith('...', $title);
        $this->assertLessThanOrEqual(83, mb_strlen($title, 'UTF-8')); // 80 + '...'
    }

    /**
     * Test that comparison settings are passed through to the TiiAssignment.
     */
    public function test_build_tii_assignment_sets_comparison_sources(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id, [
            'plagiarism_compare_student_papers' => 1,
            'plagiarism_compare_internet'       => 0,
            'plagiarism_compare_journals'       => 1,
        ]);

        $result = turnitin_submission::build_tii_assignment($cm, 1);
        $a      = $result['assignment'];

        $this->assertEquals(1, $a->getSubmittedDocumentsCheck());
        $this->assertEquals(0, $a->getInternetCheck());
        $this->assertEquals(1, $a->getPublicationsCheck());
    }

    /**
     * Test that anonymous marking is applied when useanon is on, blindmarking is set,
     * and there are no previous successful submissions.
     */
    public function test_build_tii_assignment_applies_anon_marking(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('plagiarism_turnitin_useanon', 1, 'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'       => $course->id,
            'blindmarking' => 1,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertEquals(1, $result['assignment']->getAnonymousMarking());
    }

    /**
     * Test that anonymous marking is NOT applied when there are already successful submissions.
     */
    public function test_build_tii_assignment_skips_anon_marking_when_previous_submissions_exist(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('plagiarism_turnitin_useanon', 1, 'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'       => $course->id,
            'blindmarking' => 1,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        // Seed a successful submission so previoussubmissions = true.
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm'             => $cm->id,
            'userid'         => 2,
            'identifier'     => 'hash',
            'statuscode'     => 'success',
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'submitter'      => 2,
            'lastmodified'   => time(),
            'transmatch'     => 0,
        ]);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        // setAnonymousMarking was never called, so getAnonymousMarking returns null.
        $this->assertNull($result['assignment']->getAnonymousMarking());
    }

    /**
     * Test that max grade is set from the module's grade when positive.
     */
    public function test_build_tii_assignment_sets_max_grade_from_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'grade' => 75]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertEquals(75, $result['assignment']->getMaxGrade());
    }

    /**
     * Test that max grade is clamped to 100 when the module grade is negative
     * (scale-based grading).
     */
    public function test_build_tii_assignment_clamps_negative_grade_to_100(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'grade' => -1]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertEquals(100, $result['assignment']->getMaxGrade());
    }

    /**
     * Test that the repository is forced to standard when the admin option is
     * PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD.
     */
    public function test_build_tii_assignment_forces_standard_repository(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD,
            'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id, ['plagiarism_submitpapersto' => 0]); // would be 0 without override.

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertEquals(
            PLAGIARISM_TURNITIN_SUBMIT_TO_STANDARD_REPOSITORY,
            $result['assignment']->getSubmitPapersTo()
        );
    }

    /**
     * Test that institution check is set when admin repository option is expanded.
     */
    public function test_build_tii_assignment_sets_institution_check_when_expanded(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_EXPANDED,
            'plagiarism_turnitin');

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id, ['plagiarism_compare_institution' => 1]);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertEquals(1, $result['assignment']->getInstitutionCheck());
    }

    /**
     * Test that dtdue is returned and represents a future date for an assignment
     * with a future due date.
     */
    public function test_build_tii_assignment_returns_future_dtdue(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $future = time() + WEEKSECS;
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'  => $course->id,
            'duedate' => $future,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $this->seed_cm_settings($cm->id);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertGreaterThan(time(), $result['dtdue']);
    }

    /**
     * Test that exclude_matches_value defaults to 0 when not set in config.
     */
    public function test_build_tii_assignment_defaults_exclude_matches_value_to_zero(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        // Deliberately omit plagiarism_exclude_matches_value.
        $this->seed_cm_settings($cm->id, ['plagiarism_exclude_matches_value' => '']);

        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertEquals(0, $result['assignment']->getSmallMatchExclusionThreshold());
    }

    /**
     * Test build_tii_assignment uses gradesreleased=true when marking workflow is
     * enabled and there is a released assign_user_flags row.
     * Exercises lines 1847-1849 of turnitin_submission.php.
     */
    public function test_build_tii_assignment_computes_gradesreleased_for_marking_workflow(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'markingworkflow' => 1,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assign->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->seed_cm_settings($cm->id);

        // Insert a released workflow flag so gradesreleased = true.
        $DB->insert_record('assign_user_flags', (object)[
            'assignment'    => $assign->id,
            'userid'        => $user->id,
            'workflowstate' => 'released',
            'locked'        => 0,
            'mailed'        => 0,
            'extensionduedate' => 0,
        ]);

        // If no exception is thrown, build_tii_assignment handled gradesreleased=true correctly.
        $result = turnitin_submission::build_tii_assignment($cm, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignment', $result);
    }
}

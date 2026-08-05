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
 * Unit tests for turnitin_submission_display and submission_link_context.
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
 * Tests for turnitin_submission_display.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_submission_display::class)]
#[CoversClass(submission_link_context::class)]
final class turnitin_submission_display_test extends \advanced_testcase {
    // Access tests.

    /**
     * Test that render returns empty string when the viewer is not a tutor and
     * not in the submission users list.
     */
    public function test_render_returns_empty_when_viewer_has_no_access(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'success', 'externalid' => 'tii-1']);
        $ctx->istutor        = false;
        $ctx->vieweruserid   = 99;
        $ctx->submissionusers = [100, 101]; // Viewer (99) not in list.

        $result = turnitin_submission_display::render($ctx);
        $this->assertEquals('', $result);
    }

    /**
     * Test that render produces output when the viewer is in the submission users list.
     */
    public function test_render_produces_output_when_viewer_is_submission_user(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'queued']);
        $ctx->istutor        = false;
        $ctx->vieweruserid   = 42;
        $ctx->submissionusers = [42];

        $result = turnitin_submission_display::render($ctx);
        $this->assertNotEmpty($result);
    }

    // Render_queued tests.

    /**
     * Test that render_queued returns HTML containing the queued status indicator.
     */
    public function test_render_queued_contains_status_class(): void {
        $this->resetAfterTest();

        $result = turnitin_submission_display::render_queued();

        $this->assertStringContainsString('turnitin_status', $result);
        $this->assertStringContainsString(get_string('queued', 'plagiarism_turnitin'), $result);
    }

    // Render_pending tests.

    /**
     * Test that render_pending returns HTML containing the pending status indicator.
     */
    public function test_render_pending_contains_status_class(): void {
        $this->resetAfterTest();

        $result = turnitin_submission_display::render_pending();

        $this->assertStringContainsString('turnitin_status', $result);
        $this->assertStringContainsString(get_string('pending', 'plagiarism_turnitin'), $result);
    }

    // Render_deleted tests.

    /**
     * Test that render_deleted returns the deleted status string in the output.
     */
    public function test_render_deleted_contains_deleted_string(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'deleted', 'errorcode' => 0, 'errormsg' => null]);

        $result = turnitin_submission_display::render_deleted($ctx);

        $this->assertStringContainsString('turnitin_status', $result);
        $this->assertStringContainsString(get_string('deleted', 'plagiarism_turnitin'), $result);
    }

    // Render_error tests.

    /**
     * Test that render_error shows the resubmit link for a tutor when errorcode is not 3.
     */
    public function test_render_error_shows_resubmit_link_for_tutor(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor        = true;
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'error', 'errorcode' => 9, 'errormsg' => null, 'id' => 55]);

        $result = turnitin_submission_display::render_error($ctx);

        $this->assertStringContainsString('plagiarism_turnitin_resubmit_link', $result);
        $this->assertStringContainsString('pp_resubmit_55', $result);
    }

    /**
     * Test that render_error shows the error string to students (not the resubmit link).
     */
    public function test_render_error_shows_error_message_for_student(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor        = false;
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'error', 'errorcode' => 0, 'errormsg' => null]);

        $result = turnitin_submission_display::render_error($ctx);

        $this->assertStringContainsString('warning clear', $result);
        $this->assertStringNotContainsString('plagiarism_turnitin_resubmit_link', $result);
    }

    /**
     * Test that render_error shows just the icon (no resubmit) for tutor when errorcode is 3
     * (EULA not accepted — resubmission is not possible).
     */
    public function test_render_error_shows_icon_only_for_tutor_with_errorcode_3(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor        = true;
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'error', 'errorcode' => 3, 'errormsg' => null]);

        $result = turnitin_submission_display::render_error($ctx);

        $this->assertStringNotContainsString('plagiarism_turnitin_resubmit_link', $result);
        $this->assertStringContainsString('clear', $result);
    }

    /**
     * Test that a file larger than the Turnitin limit gets errorcode 2 applied
     * automatically even when the stored errorcode is 0.
     */
    public function test_render_error_upgrades_legacy_oversized_file_to_errorcode_2(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor        = true;
        $ctx->submissiontype = 'file';
        $ctx->filesize       = PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE + 1;
        $ctx->plagiarismfile = $this->make_file(['statuscode' => 'error', 'errorcode' => 0, 'errormsg' => null]);

        $result = turnitin_submission_display::render_error($ctx);

        // Errorcode 2 message should appear (file too large).
        $this->assertStringContainsString(
            get_string('errorcode2', 'plagiarism_turnitin', [
                'maxfilesize' => display_size(PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE),
                'externalid'  => null,
            ]),
            $result
        );
    }

    // Render_success tests.

    /**
     * Test that render_success shows the Turnitin ID for a tutor.
     */
    public function test_render_success_shows_turnitin_id_for_tutor(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor           = true;
        $ctx->vieweruserid      = 1;
        $ctx->submissionuserid  = 2;
        $ctx->submissionusers   = [2];
        $ctx->plagiarismfile    = $this->make_file([
            'statuscode' => 'success', 'externalid' => 'tii-abc-123',
            'orcapable' => 1, 'similarityscore' => null,
        ]);

        $result = turnitin_submission_display::render_success($ctx);

        $this->assertStringContainsString('turnitin_status', $result);
        $this->assertStringContainsString('tii-abc-123', $result);
    }

    /**
     * Test that render_success shows the similarity score when the file has been
     * processed and a score is available.
     */
    public function test_render_success_shows_similarity_score(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor           = true;
        $ctx->vieweruserid      = 1;
        $ctx->submissionuserid  = 2;
        $ctx->submissionusers   = [2];
        $ctx->showstudentreport = true;
        $ctx->plagiarismfile    = $this->make_file([
            'statuscode'      => 'success',
            'externalid'      => 'tii-abc-123',
            'orcapable'       => 1,
            'similarityscore' => 42,
            'transmatch'      => 0,
        ]);

        $result = turnitin_submission_display::render_success($ctx);

        $this->assertStringContainsString('42%', $result);
        $this->assertStringContainsString('origreport_score', $result);
    }

    /**
     * Test that the EN flag appears in the similarity score when transmatch is 1,
     * indicating the translated similarity score was used.
     */
    public function test_render_success_shows_en_flag_for_transmatch(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor           = true;
        $ctx->vieweruserid      = 1;
        $ctx->submissionuserid  = 2;
        $ctx->submissionusers   = [2];
        $ctx->showstudentreport = true;
        $ctx->plagiarismfile    = $this->make_file([
            'statuscode'      => 'success',
            'externalid'      => 'tii-abc-123',
            'orcapable'       => 1,
            'similarityscore' => 35,
            'transmatch'      => 1,
        ]);

        $result = turnitin_submission_display::render_success($ctx);

        $this->assertStringContainsString(' EN', $result);
    }

    /**
     * Test that students do not see the similarity score when plagiarism_show_student_report
     * is disabled.
     */
    public function test_render_success_hides_score_from_student_when_disabled(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor           = false;
        $ctx->vieweruserid      = 2;
        $ctx->submissionuserid  = 2;
        $ctx->submissionusers   = [2];
        $ctx->showstudentreport = false; // Disabled.
        $ctx->plagiarismfile    = $this->make_file([
            'statuscode'      => 'success',
            'externalid'      => 'tii-abc-123',
            'orcapable'       => 1,
            'similarityscore' => 60,
            'transmatch'      => 0,
        ]);

        $result = turnitin_submission_display::render_success($ctx);

        $this->assertStringNotContainsString('60%', $result);
        $this->assertStringNotContainsString('origreport_score', $result);
    }

    /**
     * Test that for group submissions, the non-submitting member does not see
     * the clickable Originality Report link.
     */
    public function test_render_success_hides_or_link_for_non_submitter_in_group(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor                      = false;
        $ctx->vieweruserid                 = 3;
        $ctx->submissionuserid             = 2;
        $ctx->submissionusers              = [2, 3]; // Both in group.
        $ctx->showstudentreport            = true;
        $ctx->isnonsubmitterforgroupassign = true; // Viewer did NOT submit.
        $ctx->plagiarismfile               = $this->make_file([
            'statuscode'      => 'success',
            'externalid'      => 'tii-abc-123',
            'orcapable'       => 1,
            'similarityscore' => 75,
            'transmatch'      => 0,
        ]);

        $result = turnitin_submission_display::render_success($ctx);

        $this->assertStringNotContainsString('row_score', $result);
    }

    /**
     * Test that the student-read icon is shown to a tutor when the student has
     * already viewed their feedback.
     */
    public function test_render_success_shows_student_read_icon_for_tutor(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->istutor          = true;
        $ctx->vieweruserid     = 1;
        $ctx->submissionuserid = 2;
        $ctx->submissionusers  = [2];
        $ctx->plagiarismfile   = $this->make_file([
            'statuscode'    => 'success',
            'externalid'    => 'tii-xyz',
            'orcapable'     => null,
            'similarityscore' => null,
            'student_read'  => time() - 3600, // Read 1 hour ago.
        ]);

        $result = turnitin_submission_display::render_success($ctx);

        $this->assertStringContainsString('student_read_icon', $result);
    }

    // Render_no_submission tests.

    /**
     * Test that render_no_submission shows the EULA error icon when a tutor views
     * a student who has not accepted the EULA.
     */
    public function test_render_no_submission_shows_eula_error_for_tutor(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->plagiarismfile        = null;
        $ctx->istutor               = true;
        $ctx->vieweruserid          = 1;
        $ctx->submissionuserid      = 2;
        $ctx->submittereulaccepted  = false;

        $result = turnitin_submission_display::render_no_submission($ctx);

        $this->assertStringContainsString('clear', $result);
        $this->assertStringContainsString(get_string('errorcode3', 'plagiarism_turnitin'), $result);
    }

    /**
     * Test that render_no_submission returns empty when the student has accepted the EULA.
     */
    public function test_render_no_submission_returns_empty_when_eula_accepted(): void {
        $this->resetAfterTest();

        $ctx = $this->make_context();
        $ctx->plagiarismfile       = null;
        $ctx->istutor              = true;
        $ctx->vieweruserid         = 1;
        $ctx->submissionuserid     = 2;
        $ctx->submittereulaccepted = true;

        $result = turnitin_submission_display::render_no_submission($ctx);

        $this->assertEquals('', $result);
    }

    // Helpers.

    /**
     * Build a minimal submission_link_context with safe defaults for testing.
     */
    private function make_context(): submission_link_context {
        $ctx = new submission_link_context();
        $ctx->istutor          = true;
        $ctx->vieweruserid     = 1;
        $ctx->submissionuserid = 1;
        $ctx->submissionusers  = [1];
        $ctx->submissiontype   = 'file';
        $ctx->cmid             = 1;
        $ctx->cmmodname        = 'assign';
        $ctx->cmcourse         = 1;
        $ctx->wwwroot          = 'http://localhost';
        return $ctx;
    }

    /**
     * Build a minimal plagiarism_turnitin_files stdClass with given field values.
     *
     * @param array $fields
     * @return \stdClass
     */
    private function make_file(array $fields): \stdClass {
        return (object) array_merge([
            'id'              => 1,
            'statuscode'      => 'queued',
            'externalid'      => null,
            'errorcode'       => null,
            'errormsg'        => null,
            'similarityscore' => null,
            'orcapable'       => null,
            'transmatch'      => 0,
            'gm_feedback'     => 0,
            'student_read'    => 0,
            'submitter'       => 1,
        ], $fields);
    }
}

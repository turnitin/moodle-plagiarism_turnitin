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
 * Unit tests for (some of) plagiarism/turnitin/classes/modules/turnitin_assign.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2017 Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');

use PHPUnit\Framework\Attributes\CoversClass;
use plagiarism_turnitin\modules\turnitin_assign;

/**
 * Tests for assign
 *
 * @package turnitin
 */
#[CoversClass(turnitin_assign::class)]
final class turnitin_assign_test extends \advanced_testcase {

    /** @var stdClass created in setUp. */
    protected $course;

    /** @var stdClass created in setUp. */
    protected $assign;

    /** @var stdClass created in setUp. */
    protected $student;

    /**
     * Create a course, assignment and enrolled student.
     */
    public function setUp(): void {
        parent::setUp();

        $this->course = $this->getDataGenerator()->create_course();

        $this->student = $this->getDataGenerator()->create_user();
        $studentrole = get_archetype_roles('student');
        $studentrole = reset($studentrole);
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);

        $this->assign = $this->getDataGenerator()->create_module('assign', [
            'course'                              => $this->course->id,
            'name'                                => 'assignment',
            'assignsubmission_file_enabled'       => 1,
            'assignsubmission_file_maxfiles'      => 1,
            'assignsubmission_file_maxsizebytes'  => 1024 * 1024,
            'assignsubmission_onlinetext_enabled' => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // Existing is_resubmission_allowed tests
    // -------------------------------------------------------------------------

    /**
     * Test to check whether resubmissions are allowed.
     */
    public function test_check_is_resubmission_allowed(): void {
        $this->resetAfterTest(true);

        $moduleobject = new turnitin_assign();

        $this->assertTrue($moduleobject->is_resubmission_allowed($this->assign->id, 1, 'file', 1));
        $this->assertTrue($moduleobject->is_resubmission_allowed($this->assign->id, 1, 'text_content', 1));
        $this->assertFalse($moduleobject->is_resubmission_allowed($this->assign->id, 1, 'text_content', 5));
        $this->assertFalse($moduleobject->is_resubmission_allowed($this->assign->id, 0, 'file', 1));
        $this->assertFalse($moduleobject->is_resubmission_allowed($this->assign->id, 0, 'text_content', 1));
        $this->assertFalse($moduleobject->is_resubmission_allowed($this->assign->id, 1, 'file', 5));
    }

    /**
     * Test that resubmissions are not allowed for files if the maximum files in a submission is more than 1.
     */
    public function test_check_is_resubmission_allowed_maxfiles_above_threshold(): void {
        $this->resetAfterTest(true);

        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'                             => $this->course->id,
            'name'                               => 'assignment',
            'assignsubmission_file_enabled'      => 1,
            'assignsubmission_file_maxfiles'     => 2,
            'assignsubmission_file_maxsizebytes' => 10,
        ]);

        $moduleobject = new turnitin_assign();
        $this->assertFalse($moduleobject->is_resubmission_allowed($assign->id, 1, 'file', 1));
        $this->assertTrue($moduleobject->is_resubmission_allowed($assign->id, 1, 'text_content', 1));
    }

    // -------------------------------------------------------------------------
    // get_submission_content — file submissions
    // -------------------------------------------------------------------------

    /**
     * Test that a valid file submission returns its content, the original filename as
     * title, and createSubmission for a first-time submission.
     */
    public function test_get_submission_content_returns_file_content_for_new_submission(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_file_submission('essay.docx', 'Hello world');

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, ['.docx']
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
        $this->assertEquals('Hello world', $result['textcontent']);
        $this->assertEquals('essay.docx', $result['title']);
        $this->assertEquals('essay.docx', $result['filename']);
    }

    /**
     * Test that a file resubmission uses replaceSubmission when resubmission is allowed.
     */
    public function test_get_submission_content_file_uses_replace_when_resubmission_allowed(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_file_submission('essay.docx', 'Hello world');
        $queueditem->externalid = 'tii-existing-id';
        $moduledata->resubmission_allowed = true;

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, ['.docx']
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('replaceSubmission', $result['apimethod']);
    }

    /**
     * Test that a file resubmission uses createSubmission when resubmission is not allowed,
     * even if an externalid exists. The old submission will be replaced by a new one.
     */
    public function test_get_submission_content_file_uses_create_when_resubmission_not_allowed(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_file_submission('essay.docx', 'Hello world');
        $queueditem->externalid = 'tii-existing-id';
        $moduledata->resubmission_allowed = false;

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, ['.docx']
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
    }

    /**
     * Test that a file with an unsupported extension returns errorcode 16 when the
     * assignment does not allow any file type.
     */
    public function test_get_submission_content_file_returns_error_for_unsupported_extension(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_file_submission('notes.xyz', 'content');

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, ['.docx', '.pdf']
        );

        $this->assertEquals(16, $result['errorcode']);
    }

    /**
     * Test that a file with an unsupported extension is accepted when the assignment
     * is configured to allow any file type (acceptanyfiletype = true).
     */
    public function test_get_submission_content_file_accepts_any_extension_when_configured(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_file_submission('notes.xyz', 'content');

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, true, ['.docx', '.pdf']
        );

        $this->assertEquals(0, $result['errorcode']);
    }

    /**
     * Test that a missing file returns errorcode 9.
     */
    public function test_get_submission_content_file_returns_error_when_file_not_found(): void {
        $this->resetAfterTest();

        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $queueditem = $this->make_queued_item($this->student->id, 0, 'file', 'nonexistenthash');
        $moduledata = $this->make_moduledata(false, false);

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, ['.docx']
        );

        $this->assertEquals(9, $result['errorcode']);
    }

    // -------------------------------------------------------------------------
    // get_submission_content — text_content submissions
    // -------------------------------------------------------------------------

    /**
     * Test that an online text submission returns the plain text content and correct
     * title format for a first-time submission.
     */
    public function test_get_submission_content_returns_text_content_for_new_submission(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_text_submission('<p>My essay &amp; thoughts</p>');

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, []
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
        // html_to_text() should decode entities and strip tags.
        $this->assertStringContainsString('My essay & thoughts', $result['textcontent']);
        $this->assertStringNotContainsString('&amp;', $result['textcontent']);
        $this->assertStringStartsWith('onlinetext_', $result['title']);
        $this->assertEquals($result['title'], $result['filename']);
    }

    /**
     * Test that the title for a text_content submission contains the user id, cm id
     * and instance id so submissions from different users and assignments don't collide.
     */
    public function test_get_submission_content_text_title_contains_identifying_components(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $moduledata] = $this->create_text_submission('Some content');

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, []
        );

        $expectedtitle = 'onlinetext_' . $this->student->id . '_' . $cm->id . '_' . $cm->instance . '.txt';
        $this->assertEquals($expectedtitle, $result['title']);
    }

    /**
     * Test that a team submission uses userid 0 when looking up the online text, since
     * group submissions are stored against userid = 0 in assign_submission.
     */
    public function test_get_submission_content_text_uses_userid_zero_for_team_submission(): void {
        $this->resetAfterTest();

        $teamassign = $this->getDataGenerator()->create_module('assign', [
            'course'                              => $this->course->id,
            'name'                                => 'team assignment',
            'assignsubmission_onlinetext_enabled' => 1,
            'teamsubmission'                      => 1,
        ]);

        [$cm, $queueditem, $moduledata] = $this->create_text_submission(
            '<p>Team effort</p>', $teamassign, $this->student->id, true
        );

        $result = (new turnitin_assign())->get_submission_content(
            $queueditem, $cm, $moduledata, false, []
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertStringContainsString('Team effort', $result['textcontent']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Store a file in the Moodle file API and return the cm, queued item and module data
     * needed to test get_submission_content() for a file submission.
     *
     * @return array [cm, queueditem, moduledata]
     */
    private function create_file_submission(string $filename, string $content): array {
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);

        $fs = get_file_storage();
        $filerecord = [
            'contextid' => \context_module::instance($cm->id)->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
        $file = $fs->create_file_from_string($filerecord, $content);

        $queueditem = $this->make_queued_item($this->student->id, 1, 'file', $file->get_pathnamehash());
        $moduledata = $this->make_moduledata(false, false);

        return [$cm, $queueditem, $moduledata];
    }

    /**
     * Create an online text submission and return the cm, queued item and module data
     * needed to test get_submission_content() for a text_content submission.
     *
     * @return array [cm, queueditem, moduledata]
     */
    private function create_text_submission(
        string $text,
        ?\stdClass $assign = null,
        ?int $userid = null,
        bool $teamsubmission = false
    ): array {
        global $DB;

        $assign  = $assign  ?? $this->assign;
        $userid  = $userid  ?? $this->student->id;
        $cm      = get_coursemodule_from_instance('assign', $assign->id);

        // The submission userid is 0 for team submissions.
        $submissionuserid = $teamsubmission ? 0 : $userid;

        $submission = new \stdClass();
        $submission->assignment  = $assign->id;
        $submission->userid      = $submissionuserid;
        $submission->status      = 'submitted';
        $submission->timemodified = time();
        $submission->timecreated  = time();
        $submission->attemptnumber = 0;
        $submission->latest       = 1;
        $submission->groupid      = 0;
        $submission->id = $DB->insert_record('assign_submission', $submission);

        $onlinetext = new \stdClass();
        $onlinetext->submission  = $submission->id;
        $onlinetext->assignment  = $assign->id;
        $onlinetext->onlinetext  = $text;
        $onlinetext->onlineformat = FORMAT_HTML;
        $DB->insert_record('assignsubmission_onlinetext', $onlinetext);

        $queueditem = $this->make_queued_item($userid, $submission->id, 'text_content', sha1($text));
        $moduledata = $this->make_moduledata($teamsubmission, false);

        return [$cm, $queueditem, $moduledata];
    }

    /**
     * Build a minimal queued item as would be read from plagiarism_turnitin_files.
     */
    private function make_queued_item(
        int $userid,
        int $itemid,
        string $submissiontype,
        string $identifier,
        ?string $externalid = null
    ): \stdClass {
        $item = new \stdClass();
        $item->userid         = $userid;
        $item->itemid         = $itemid;
        $item->submissiontype = $submissiontype;
        $item->identifier     = $identifier;
        $item->externalid     = $externalid;
        return $item;
    }

    /**
     * Build a minimal module data object as would be read from the assign DB table.
     */
    private function make_moduledata(bool $teamsubmission, bool $resubmissionallowed): \stdClass {
        $data = new \stdClass();
        $data->teamsubmission      = $teamsubmission ? 1 : 0;
        $data->resubmission_allowed = $resubmissionallowed;
        return $data;
    }
}

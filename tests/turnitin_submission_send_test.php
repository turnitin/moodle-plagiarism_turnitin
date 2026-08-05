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
 * Unit tests for turnitin_submission::build_submission_content and
 * turnitin_submission::build_tii_submission_object — the two blocks extracted
 * from plagiarism_turnitin_send_single_submission.
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
 * Tests for the submission-content and TiiSubmission-builder helpers extracted
 * from plagiarism_turnitin_send_single_submission.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_submission::class)]
final class turnitin_submission_send_test extends \advanced_testcase {
    /**
     * Build the minimal stubs needed to call build_submission_content.
     *
     * @param array $queuedoverrides Fields to override on the default queueditem stub.
     * @return array{queueditem, cm, moduledata, settings, user, plugin}
     */
    private function make_content_fixtures(
        array $queuedoverrides = [],
        string $modname = 'assign'
    ): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module($modname, ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance($modname, $mod->id);
        $muser  = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($muser->id, $course->id);

        $moduledata                    = $DB->get_record($modname, ['id' => $mod->id]);
        $moduledata->resubmission_allowed = false;

        $queueditem = (object) array_merge([
            'id'             => 99,
            'cm'             => $cm->id,
            'userid'         => $muser->id,
            'submitter'      => $muser->id,
            'submissiontype' => 'text_content',
            'identifier'     => sha1('content'),
            'itemid'         => 0,
            'externalid'     => null,
            'attempt'        => 0,
        ], $queuedoverrides);

        // Minimal user stub — build_submission_content only reads ->id from $user.
        $user        = new \stdClass();
        $user->id    = $muser->id;
        $user->tiiuserid = 0;

        $settings = [
            'plagiarism_report_gen'               => 0,
            'plagiarism_allow_non_or_submissions'  => 0,
        ];

        $plugin = new \plagiarism_plugin_turnitin();

        return compact('cm', 'mod', 'course', 'muser', 'moduledata', 'queueditem', 'user', 'settings', 'plugin');
    }

    // Tests for build_submission_content() — one per submission type.

    /**
     * Test build_submission_content returns errorcode=0 and sets apimethod/title/filename
     * for a valid assign file submission. Exercises the 'file' case.
     */
    public function test_build_submission_content_file_type_valid(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $f = $this->make_content_fixtures(['submissiontype' => 'file']);

        // Create a real .docx file so get_file_by_hash returns it.
        $fs      = get_file_storage();
        $context = \context_module::instance($f['cm']->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'essay.docx',
            'userid'    => $f['muser']->id,
            'source'    => 'essay.docx',
        ], 'essay content');

        // Create an assign_submission row so the module can find the attempt.
        $DB->insert_record('assign_submission', (object)[
            'assignment'    => $f['mod']->id,
            'userid'        => $f['muser']->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);

        $f['queueditem']->identifier     = $file->get_pathnamehash();
        $f['queueditem']->submissiontype = 'file';

        $moduleobject = new modules\turnitin_assign();
        $acceptedfiles = ['.doc', '.docx', '.pdf', '.txt'];

        $result = turnitin_submission::build_submission_content(
            $f['queueditem'],
            $f['cm'],
            $f['moduledata'],
            $moduleobject,
            $f['settings'],
            $f['user'],
            $f['plugin'],
            $acceptedfiles
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertNotEmpty($result['title']);
        $this->assertNotEmpty($result['filename']);
        $this->assertNotEmpty($result['textcontent']);
    }

    /**
     * Test build_submission_content returns errorcode>0 for an unsupported file extension.
     * Exercises the 'file' case error path (mtrace + errorcode).
     */
    public function test_build_submission_content_file_type_bad_extension(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $f = $this->make_content_fixtures(['submissiontype' => 'file']);

        $fs      = get_file_storage();
        $context = \context_module::instance($f['cm']->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 2,
            'filepath'  => '/',
            'filename'  => 'data.xyz',
            'userid'    => $f['muser']->id,
            'source'    => 'data.xyz',
        ], 'binary');

        $f['queueditem']->identifier     = $file->get_pathnamehash();
        $f['queueditem']->submissiontype = 'file';

        $moduleobject = new modules\turnitin_assign();

        ob_start();
        $result = turnitin_submission::build_submission_content(
            $f['queueditem'],
            $f['cm'],
            $f['moduledata'],
            $moduleobject,
            $f['settings'],
            $f['user'],
            $f['plugin'],
            ['.doc', '.docx']
        );
        ob_end_clean();

        $this->assertGreaterThan(0, $result['errorcode']);
    }

    /**
     * Test build_submission_content handles assign text_content submissions.
     * Exercises the 'text_content' assign branch.
     */
    public function test_build_submission_content_text_content_assign(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $f = $this->make_content_fixtures(['submissiontype' => 'text_content']);

        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $f['mod']->id,
            'userid'        => $f['muser']->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);
        $DB->insert_record('assignsubmission_onlinetext', (object)[
            'assignment'   => $f['mod']->id,
            'submission'   => $submissionid,
            'onlinetext'   => 'My essay text',
            'onlineformat' => FORMAT_HTML,
        ]);

        $f['queueditem']->itemid = $submissionid;

        $moduleobject = new modules\turnitin_assign();

        $result = turnitin_submission::build_submission_content(
            $f['queueditem'],
            $f['cm'],
            $f['moduledata'],
            $moduleobject,
            $f['settings'],
            $f['user'],
            $f['plugin'],
            []
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertStringContainsString('My essay text', $result['textcontent']);
    }

    /**
     * Test build_submission_content handles workshop text_content submissions —
     * exercises the workshop branch (lines covering the workshop case).
     */
    public function test_build_submission_content_text_content_workshop(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/workshop/lib.php');

        $course   = $this->getDataGenerator()->create_course();
        $workshop = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $cm       = get_coursemodule_from_instance('workshop', $workshop->id);
        $muser    = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($muser->id, $course->id);

        $submissionid = $DB->insert_record('workshop_submissions', (object)[
            'workshopid'   => $workshop->id,
            'authorid'     => $muser->id,
            'title'        => 'My workshop submission',
            'content'      => '<p>Workshop essay text</p>',
            'contentformat' => FORMAT_HTML,
            'late'         => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $moduledata = $DB->get_record('workshop', ['id' => $workshop->id]);
        $moduledata->resubmission_allowed = false;

        $queueditem = (object)[
            'id'             => 88,
            'cm'             => $cm->id,
            'userid'         => $muser->id,
            'submitter'      => $muser->id,
            'submissiontype' => 'text_content',
            'identifier'     => sha1('workshop content'),
            'itemid'         => $submissionid,
            'externalid'     => null,
            'attempt'        => 0,
        ];

        $user        = new \stdClass();
        $user->id    = $muser->id;
        $user->tiiuserid = 0;

        $moduleobject = new modules\turnitin_workshop();
        $settings     = ['plagiarism_report_gen' => 0, 'plagiarism_allow_non_or_submissions' => 0];
        $plugin       = new \plagiarism_plugin_turnitin();

        $result = turnitin_submission::build_submission_content(
            $queueditem,
            $cm,
            $moduledata,
            $moduleobject,
            $settings,
            $user,
            $plugin,
            []
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
        $this->assertStringContainsString('Workshop essay text', $result['textcontent']);
        $this->assertStringContainsString('.txt', $result['filename']);
    }

    /**
     * Test build_submission_content handles forum_post submissions where the forum
     * post exists in the DB. Exercises the forum_post case.
     */
    public function test_build_submission_content_forum_post_found(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $f = $this->make_content_fixtures(['submissiontype' => 'forum_post'], 'forum');

        // Create the minimal forum tables needed.
        $discussionid = $DB->insert_record('forum_discussions', (object)[
            'course'       => $f['course']->id,
            'forum'        => $f['mod']->id,
            'name'         => 'Test discussion',
            'firstpost'    => 0,
            'userid'       => $f['muser']->id,
            'groupid'      => 0,
            'assessed'     => 0,
            'timemodified' => time(),
            'usermodified' => $f['muser']->id,
            'timestart'    => 0,
            'timeend'      => 0,
        ]);
        $postid = $DB->insert_record('forum_posts', (object)[
            'discussion' => $discussionid,
            'parent'     => 0,
            'userid'     => $f['muser']->id,
            'created'    => time(),
            'modified'   => time(),
            'mailed'     => 0,
            'subject'    => 'Test post',
            'message'    => 'Forum post content here',
            'messageformat' => FORMAT_HTML,
            'messagetrust'  => 0,
            'attachment'    => 0,
            'totalscore'    => 0,
            'mailnow'       => 0,
            'wordcount'     => 4,
        ]);

        $f['queueditem']->submissiontype = 'forum_post';
        $f['queueditem']->itemid         = $postid;
        $f['queueditem']->userid         = $f['muser']->id;

        $moduleobject = new modules\turnitin_forum();

        $result = turnitin_submission::build_submission_content(
            $f['queueditem'],
            $f['cm'],
            $f['moduledata'],
            $moduleobject,
            $f['settings'],
            $f['user'],
            $f['plugin'],
            []
        );

        $this->assertEquals(0, $result['errorcode']);
        $this->assertStringContainsString('Forum post content here', $result['textcontent']);
    }

    /**
     * Test build_submission_content returns errorcode>0 when forum post is not found.
     * Exercises the forum_post error mtrace path.
     */
    public function test_build_submission_content_forum_post_not_found(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $f = $this->make_content_fixtures(['submissiontype' => 'forum_post', 'itemid' => 99999], 'forum');
        $f['queueditem']->submissiontype = 'forum_post';

        $moduleobject = new modules\turnitin_forum();

        ob_start();
        $result = turnitin_submission::build_submission_content(
            $f['queueditem'],
            $f['cm'],
            $f['moduledata'],
            $moduleobject,
            $f['settings'],
            $f['user'],
            $f['plugin'],
            []
        );
        ob_end_clean();

        $this->assertEquals(9, $result['errorcode']);
    }

    /**
     * Test build_submission_content handles workshop text_content with an existing
     * externalid and resubmission_allowed=true → apimethod='replaceSubmission'.
     * Exercises the workshop externalid/resubmission branch.
     */
    public function test_build_submission_content_workshop_uses_replace_when_resubmission_allowed(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/workshop/lib.php');

        // Credentials so turnitin_comms doesn't throw if delete() is called.
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'TESTKEY', 'plagiarism_turnitin');

        $course   = $this->getDataGenerator()->create_course();
        $workshop = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $cm       = get_coursemodule_from_instance('workshop', $workshop->id);
        $muser    = $this->getDataGenerator()->create_user();

        $submissionid = $DB->insert_record('workshop_submissions', (object)[
            'workshopid'    => $workshop->id,
            'authorid'      => $muser->id,
            'title'         => 'WS submission',
            'content'       => 'Some content',
            'contentformat' => FORMAT_HTML,
            'late'          => 0,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);

        $moduledata = $DB->get_record('workshop', ['id' => $workshop->id]);
        $moduledata->resubmission_allowed = true;

        $queueditem = (object)[
            'id' => 77, 'cm' => $cm->id,
            'userid' => $muser->id, 'submitter' => $muser->id,
            'submissiontype' => 'text_content',
            'identifier' => 'hash', 'itemid' => $submissionid,
            'externalid' => 'ext-ws-resubmit', 'attempt' => 0,
        ];

        $user = (object)['id' => $muser->id, 'tiiuserid' => 0];
        $moduleobject = new modules\turnitin_workshop();
        // Report_gen=1 so the delete() branch (which needs API) is skipped.
        $settings = ['plagiarism_report_gen' => 1, 'plagiarism_allow_non_or_submissions' => 0];

        ob_start();
        $result = turnitin_submission::build_submission_content(
            $queueditem,
            $cm,
            $moduledata,
            $moduleobject,
            $settings,
            $user,
            new \plagiarism_plugin_turnitin(),
            []
        );
        ob_end_clean();

        $this->assertEquals('replaceSubmission', $result['apimethod']);
    }

    // Tests for build_tii_submission_object().

    /**
     * Test build_tii_submission_object sets all required fields for a normal
     * createSubmission call (author == submitter).
     */
    public function test_build_tii_submission_object_create(): void {
        $this->resetAfterTest();

        $queueditem = (object)[
            'userid'         => 5,
            'submitter'      => 5, // Same as userid.
            'externalid'     => null,
            'itemid'         => 0,
        ];

        $user              = new \stdClass();
        $user->tiiuserid   = 'tii-123';

        $syncassignment    = ['tiiassignmentid' => 'assign-456'];
        $coursedata        = (object)['turnitin_cid' => 0];
        $tempfile          = '/tmp/test.txt';

        $result = turnitin_submission::build_tii_submission_object(
            $queueditem,
            'createSubmission',
            'My Title',
            $tempfile,
            $syncassignment,
            $user,
            $coursedata
        );

        $this->assertInstanceOf(\TiiSubmission::class, $result);
        $this->assertEquals('assign-456', $result->getAssignmentId());
        $this->assertEquals('My Title', $result->getTitle());
        $this->assertEquals('tii-123', $result->getAuthorUserId());
        $this->assertEquals('tii-123', $result->getSubmitterUserId());
        $this->assertEquals('Learner', $result->getRole());
        $this->assertEquals($tempfile, $result->getSubmissionDataPath());
        // No submission ID set for createSubmission.
        $this->assertNull($result->getSubmissionId());
    }

    /**
     * Test build_tii_submission_object sets submissionId for replaceSubmission.
     */
    public function test_build_tii_submission_object_replace(): void {
        $this->resetAfterTest();

        $queueditem = (object)[
            'userid'     => 5,
            'submitter'  => 5,
            'externalid' => 'existing-tii-id',
            'itemid'     => 0,
        ];

        $user            = new \stdClass();
        $user->tiiuserid = 'tii-456';

        $result = turnitin_submission::build_tii_submission_object(
            $queueditem,
            'replaceSubmission',
            'Updated Essay',
            '/tmp/update.txt',
            ['tiiassignmentid' => 'assign-789'],
            $user,
            (object)['turnitin_cid' => 0]
        );

        // ReplaceSubmission triggers setSubmissionId with the externalid.
        $this->assertEquals('existing-tii-id', $result->getSubmissionId());
        $this->assertEquals('assign-789', $result->getAssignmentId());
    }
}

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
 * Defines turnitin_submission class
 *
 * @package   plagiarism_turnitin
 * @copyright 2012 iParadigms LLC *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Class turnitin_submission
 *
 * @package   plagiarism_turnitin
 * @copyright 2012 iParadigms LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turnitin_submission {
    /**
     * @var int
     */
    private $id;
    /**
     * @var array|mixed
     */
    private $data;
    /**
     * @var false|mixed|stdClass
     */
    private $submissiondata;
    /**
     * @var false|stdClass
     */
    private $cm;

    /**
     * Class turnitin_submission constructor.
     *
     * @param int $id
     * @param stdClass $data
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($id, $data = []) {
        global $DB;

        $this->id = $id;
        $this->data = $data;
        $this->submissiondata = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->cm = get_coursemodule_from_id('', $this->submissiondata->cm);
    }

    /**
     * Get all relevant submission data to requeue submission for the cron to process.
     */
    public function recreate_submission_event() {
        global $DB;

        // Create module object.
        $moduleclass = "turnitin_" . $this->cm->modname;
        $moduleobject = new $moduleclass();

        // Some data depends on submission type.
        switch ($this->submissiondata->submissiontype) {
            case 'file':
                $file = $this->get_file_info();

                // Collate data and trigger new event for the cron to process.
                $params = [
                    'context' => context_module::instance($this->cm->id),
                    'courseid' => $this->cm->course,
                    'objectid' => $file->get_itemid(),
                    'userid' => $this->submissiondata->userid,
                    'other' => [
                        'content' => '',
                        'pathnamehashes' => [$this->submissiondata->identifier],
                    ],
                ];
                // Forum attachments need the discussion id to be set.
                if ($this->cm->modname == "forum") {
                    $discussionid = $moduleobject->get_discussionid($this->data['forumdata']);
                    $params['other']['discussionid'] = $discussionid;
                    $params['other']['triggeredfrom'] = 'turnitin_recreate_submission_event';
                }

                $event = $moduleobject->create_file_event($params);
                if ($this->cm->modname != "forum") {
                    $event->set_legacy_files([$this->submissiondata->identifier => $file]);
                }
                $event->trigger();

                break;

            case 'text_content':
                // Get the actual text content.
                $onlinetextdata = $moduleobject->get_onlinetext($this->submissiondata->userid, $this->cm);

                // Collate data and trigger new event for the cron to process.
                $params = [
                    'context' => context_module::instance($this->cm->id),
                    'courseid' => $this->cm->course,
                    'objectid' => $onlinetextdata->itemid,
                    'userid' => $this->submissiondata->userid,
                    'other' => [
                        'pathnamehashes' => [],
                        'content' => trim($onlinetextdata->onlinetext),
                        'format' => $onlinetextdata->onlineformat,
                    ],
                ];

                $event = $moduleobject->create_text_event($params, $this->cm);
                $event->trigger();

                break;

            case 'forum_post':
                $discussionid = $moduleobject->get_discussionid($this->data['forumdata']);
                $content = base64_decode($this->data['forumpost']);

                $forum = $DB->get_record("forum", ["id" => $this->cm->instance]);

                // Some forum types don't pass in certain values on main forum page.
                if ((empty($discussionid)) && ($forum->type == 'blog' || $forum->type == 'single')) {
                    $discussion = $DB->get_record_sql(
                        'SELECT FD.id
                                                                FROM {forum_posts} FP JOIN {forum_discussions} FD
                                                                ON FP.discussion = FD.id
                                                                WHERE FD.forum = ? AND FD.course = ?
                                                                AND FP.userid = ? AND FP.message = ? ',
                        [$forum->id, $forum->course,
                                                                    $this->submissiondata->userid, $content, ]
                    );
                    $discussionid = $discussion->id;
                }

                $submission = $DB->get_record_select(
                    'forum_posts',
                    " userid = ? AND message = ? AND discussion = ? ",
                    [$this->submissiondata->userid, $content, $discussionid]
                );

                // Collate data and trigger new event for the cron to process.
                $params = [
                    'context' => context_module::instance($this->cm->id),
                    'courseid' => $this->cm->course,
                    'objectid' => $submission->id,
                    'userid' => $this->submissiondata->userid,
                    'other' => [
                        'pathnamehashes' => [],
                        'content' => trim($content),
                        'discussionid' => $discussionid,
                        'triggeredfrom' => 'turnitin_recreate_submission_event',
                    ],
                ];
                $event = \mod_forum\event\assessable_uploaded::create($params);
                $event->trigger();

                break;
        }

        $submissiondata = new \stdClass();
        $submissiondata->id = $this->id;
        $submissiondata->statuscode = 'queued';

        return $DB->update_record('plagiarism_turnitin_files', $submissiondata);
    }

    /**
     * Get the file information from Moodle. We really specifically only need the itemid.
     */
    public function get_file_info() {
        $fs = get_file_storage();

        if (!$file = $fs->get_file_by_hash($this->submissiondata->identifier)) {
            return false;
        }

        return $file;
    }

    /**
     * Update a submission row with fresh data from a Turnitin API response.
     *
     * Writes similarity score, grade, transmatch flag and feedback metadata back
     * to plagiarism_turnitin_files. When any of those values have changed, the
     * $gradeupdate callable is invoked so the caller can sync the Moodle gradebook.
     * This keeps the DB update logic testable in isolation from the gradebook coupling.
     *
     * The transmatch flag is set to 1 when the translated similarity score exceeds
     * the original — Turnitin uses this for multilingual submissions.
     *
     * @param \stdClass $cm            Course module record.
     * @param int       $submissionid  plagiarism_turnitin_files row id.
     * @param object    $tiisubmission Turnitin API submission object (TiiSubmission).
     * @param callable  $gradeupdate   Called as $gradeupdate($cm, $tiisubmission, $userid)
     *                                 when an update to the gradebook is needed.
     * @return bool True on success or when no update was needed.
     */
    public static function update(
        \stdClass $cm,
        int $submissionid,
        object $tiisubmission,
        callable $gradeupdate
    ): bool {
        global $DB;

        $return = true;
        $updaterequired = false;

        $fields = 'id, cm, userid, identifier, itemid, similarityscore, grade, submissiontype,'
            . ' orcapable, student_read, gm_feedback, errorcode';
        $submissiondata = $DB->get_record('plagiarism_turnitin_files', ['id' => $submissionid], $fields);
        if (!$submissiondata) {
            return true;
        }

        $plagiarismfile = new \stdClass();
        $plagiarismfile->id = $submissiondata->id;

        // Use the translated score when it exceeds the original — this covers multilingual submissions.
        $plagiarismfile->similarityscore = is_numeric($tiisubmission->getOverallSimilarity())
            ? $tiisubmission->getOverallSimilarity()
            : null;
        $plagiarismfile->transmatch = 0;
        if ((int)$tiisubmission->getTranslatedOverallSimilarity() > $tiisubmission->getOverallSimilarity()) {
            $plagiarismfile->similarityscore = $tiisubmission->getTranslatedOverallSimilarity();
            $plagiarismfile->transmatch = 1;
        }

        $plagiarismfile->grade      = ($tiisubmission->getGrade() == '') ? null : $tiisubmission->getGrade();
        $plagiarismfile->orcapable  = ($tiisubmission->getOriginalityReportCapable() == 1) ? 1 : 0;
        $plagiarismfile->gm_feedback = $tiisubmission->getFeedbackExists();

        // Errorcode 13 is a special Turnitin retry state — clear it and mark as success.
        if ($submissiondata->errorcode == 13) {
            $plagiarismfile->statuscode = 'success';
        }

        $plagiarismfile->errorcode = null;
        $plagiarismfile->errormsg  = null;

        $plagiarismfile->student_read = ($tiisubmission->getAuthorLastViewedFeedback() > 0)
            ? strtotime($tiisubmission->getAuthorLastViewedFeedback())
            : 0;

        if (
            $submissiondata->similarityscore != $plagiarismfile->similarityscore ||
            $submissiondata->grade != $plagiarismfile->grade           ||
            $submissiondata->orcapable != $plagiarismfile->orcapable       ||
            $submissiondata->student_read != $plagiarismfile->student_read    ||
            $submissiondata->gm_feedback != $plagiarismfile->gm_feedback
        ) {
            $updaterequired = true;
        }

        if ($updaterequired) {
            $DB->update_record('plagiarism_turnitin_files', $plagiarismfile);
            $return = $gradeupdate($cm, $tiisubmission, $submissiondata->userid);
        }

        return $return;
    }

    /**
     * Get the file information from Moodle. We really specifically only need the itemid.
        $fs = get_file_storage();

        if (!$file = $fs->get_file_by_hash($this->submissiondata->identifier)) {
            return false;
        }

        return $file;
    }

    /**
     * Mark an existing submission row as errored and increment its attempt counter.
     *
     * Called whenever processing fails partway through so the cron can retry or
     * the admin can see the failure reason in the errors view.
     *
     * @param int $submissionid The plagiarism_turnitin_files row id.
     * @param int $attempt      The current attempt number (will be incremented by one).
     * @param int $errorcode    Error code identifying the failure reason.
     * @return bool True on success.
     */
    public static function save_errored(int $submissionid, int $attempt, int $errorcode): bool {
        global $DB;

        $plagiarismfile = new \stdClass();
        $plagiarismfile->id        = $submissionid;
        $plagiarismfile->statuscode = 'error';
        $plagiarismfile->attempt   = $attempt + 1;
        $plagiarismfile->errorcode = $errorcode;

        if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
            turnitin_logger::log(
                'Update record failed (Submission: ' . $submissionid . ') - ',
                'PP_UPDATE_SUB_ERROR'
            );
        }

        return true;
    }

    /**
     * Persist a submission row — inserting when $submissionid is 0, updating otherwise.
     *
     * The attempt counter is always incremented by one from $attempt so that each
     * processing pass is recorded even if the status stays the same.
     *
     * @param \stdClass   $cm             Course module record.
     * @param int         $userid         Moodle user id of the submitting student.
     * @param int         $submissionid   Existing row id, or 0 to insert a new row.
     * @param string      $identifier     Pathnamehash (file) or content hash (text).
     * @param string      $statuscode     'queued', 'pending', 'success', or 'error'.
     * @param string|null $tiisubmissionid Turnitin submission UUID, or null if not yet submitted.
     * @param int         $submitter      Moodle user id of whoever triggered the submission.
     * @param int         $itemid         Moodle file/submission itemid.
     * @param string      $submissiontype One of 'file', 'text_content', 'forum_post', 'quiz_answer'.
     * @param int         $attempt        Current attempt number (stored as attempt + 1).
     * @param int|null    $errorcode      Error code, or null on success.
     * @param string|null $errormsg       Error message, or null on success.
     * @return bool True on success.
     */
    public static function save(
        \stdClass $cm,
        int $userid,
        int $submissionid,
        string $identifier,
        string $statuscode,
        ?string $tiisubmissionid,
        int $submitter,
        int $itemid,
        string $submissiontype,
        int $attempt,
        ?int $errorcode = null,
        ?string $errormsg = null
    ): bool {
        global $DB;

        $plagiarismfile = new \stdClass();
        if ($submissionid !== 0) {
            $plagiarismfile->id = $submissionid;
        }
        $plagiarismfile->cm             = $cm->id;
        $plagiarismfile->userid         = $userid;
        $plagiarismfile->identifier     = $identifier;
        $plagiarismfile->statuscode     = $statuscode;
        $plagiarismfile->similarityscore = null;
        $plagiarismfile->externalid     = $tiisubmissionid;
        $plagiarismfile->errorcode      = empty($errorcode) ? null : $errorcode;
        $plagiarismfile->errormsg       = empty($errormsg) ? null : $errormsg;
        $plagiarismfile->attempt        = $attempt + 1;
        $plagiarismfile->transmatch     = 0;
        $plagiarismfile->lastmodified   = time();
        $plagiarismfile->submissiontype = $submissiontype;
        $plagiarismfile->itemid         = $itemid;
        $plagiarismfile->submitter      = $submitter;

        if ($submissionid !== 0) {
            if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                turnitin_logger::log(
                    'Update record failed (CM: ' . $cm->id . ', User: ' . $userid . ') - ',
                    'PP_UPDATE_SUB_ERROR'
                );
            }
        } else {
            if (!$DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
                turnitin_logger::log(
                    'Insert record failed (CM: ' . $cm->id . ', User: ' . $userid . ') - ',
                    'PP_INSERT_SUB_ERROR'
                );
            }
        }

        return true;
    }

    /**
     * Insert a new queued row into plagiarism_turnitin_files.
     *
     * Used when there is no existing row for this cm/user/identifier combination.
     * The attempt counter starts at 0 and is incremented on first processing.
     *
     * @param \stdClass $cm             Course module record.
     * @param int       $userid         Moodle user id.
     * @param string    $identifier     Pathnamehash (file) or content hash (text).
     * @param string    $submissiontype One of 'file', 'text_content', 'forum_post', 'quiz_answer'.
     * @return int The new row id, or 0 on failure.
     */
    public static function create_new(\stdClass $cm, int $userid, string $identifier, string $submissiontype): int {
        global $DB;

        $plagiarismfile = new \stdClass();
        $plagiarismfile->cm             = $cm->id;
        $plagiarismfile->userid         = $userid;
        $plagiarismfile->identifier     = $identifier;
        $plagiarismfile->statuscode     = 'queued';
        $plagiarismfile->similarityscore = null;
        $plagiarismfile->attempt        = 0;
        $plagiarismfile->transmatch     = 0;
        $plagiarismfile->submissiontype = $submissiontype;

        if (!$fileid = $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
            turnitin_logger::log(
                'Insert record failed (CM: ' . $cm->id . ', User: ' . $userid . ')',
                'PP_NEW_SUB'
            );
            return 0;
        }

        return $fileid;
    }

    /**
     * Reset an existing row to pending so it will be reprocessed by the cron.
     *
     * Called when the content changes (e.g. a resubmission) — resets scores and
     * clears error state. The attempt counter is only reset to 1 when the row was
     * not previously in an error state, to preserve retry history for errored rows.
     *
     * @param \stdClass $cm                 Course module record.
     * @param int       $userid             Moodle user id (used for logging only).
     * @param string    $identifier         New identifier for the updated content.
     * @param \stdClass $currentsubmission  The existing plagiarism_turnitin_files row.
     * @param string    $submissiontype     Submission type string.
     */
    public static function reset(
        \stdClass $cm,
        int $userid,
        string $identifier,
        \stdClass $currentsubmission,
        string $submissiontype
    ): void {
        global $DB;

        $plagiarismfile = new \stdClass();
        $plagiarismfile->id             = $currentsubmission->id;
        $plagiarismfile->identifier     = $identifier;
        $plagiarismfile->statuscode     = 'pending';
        $plagiarismfile->similarityscore = null;
        if ($currentsubmission->statuscode != 'error') {
            $plagiarismfile->attempt = 1;
        }
        $plagiarismfile->transmatch     = 0;
        $plagiarismfile->submissiontype = $submissiontype;
        $plagiarismfile->orcapable      = null;
        $plagiarismfile->errormsg       = null;
        $plagiarismfile->errorcode      = null;

        if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
            turnitin_logger::log(
                'Update record failed (CM: ' . $cm->id . ', User: ' . $userid . ')',
                'PP_REPLACE_SUB'
            );
        }
    }

    /**
     * Delete a submission from Turnitin via the API.
     *
     * Exceptions are caught and logged rather than propagated so that a failed
     * deletion does not abort the submission queue processing that called this.
     *
     * @param \stdClass       $cm          Course module record (used in error logging).
     * @param string          $submissionid Turnitin submission UUID.
     * @param int             $userid       Moodle user id (used in error logging).
     * @param turnitin_comms|null $comms   Injected comms object; creates a real one if null.
     */
    public static function delete(
        \stdClass $cm,
        string $submissionid,
        int $userid,
        ?turnitin_comms $comms = null
    ): void {
        global $DB;

        $turnitincomms = $comms ?? new turnitin_comms();
        $turnitincall  = $turnitincomms->initialise_api();

        $submission = new \TiiSubmission();
        $submission->setSubmissionId($submissionid);

        try {
            $turnitincall->deleteSubmission($submission);
        } catch (\Exception $e) {
            $turnitincomms->handle_exceptions($e, 'turnitindeletionerror', false);

            $user = $DB->get_record('user', ['id' => $userid]);
            mtrace('-------------------------');
            mtrace(get_string('turnitindeletionerror', 'plagiarism_turnitin') . ': ' . $e->getMessage());
            mtrace('User:  ' . $user->id . ' - ' . $user->firstname . ' ' . $user->lastname
                . ' (' . $user->email . ')');
            mtrace('Course Module: ' . $cm->id . '');
            mtrace('-------------------------');
        }
    }
}

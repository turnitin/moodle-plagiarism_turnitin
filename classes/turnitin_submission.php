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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/turnitin/vendor/autoload.php');

use Integrations\PhpSdk\TiiSubmission;

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

        $submission = new TiiSubmission();
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

    /**
     * Mark a submission as errored when it is missing from Turnitin.
     *
     * Looks up the local row by its Turnitin externalid, then sets statuscode='error'
     * and errorcode=13. Errorcode 13 is the signal that tells the cron the submission
     * needs to be requeued — turnitin_submission::update() will clear it and set
     * statuscode='success' when Turnitin confirms the submission is back.
     *
     * @param string $externalid Turnitin submission UUID (externalid column value).
     */
    public static function invalidate_missing(string $externalid): void {
        global $DB;

        $currentsubmission = $DB->get_record(
            'plagiarism_turnitin_files',
            ['externalid' => $externalid],
            'id, externalid, userid'
        );

        $plagiarismfile = new \stdClass();
        $plagiarismfile->id         = $currentsubmission->id;
        $plagiarismfile->externalid = $currentsubmission->externalid;
        $plagiarismfile->userid     = $currentsubmission->userid;
        $plagiarismfile->statuscode = 'error';
        $plagiarismfile->errorcode  = 13;

        if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
            mtrace('File failed to update: ' . $plagiarismfile->id);
        } else {
            mtrace('File updated: ' . $plagiarismfile->id);
        }
    }

    /**
     * Check whether a submission is a group submission and return the group id if so.
     *
     * Only applicable to the assign module when teamsubmission is enabled. In all
     * other cases (non-assign module, or assign with individual submissions) returns false.
     *
     * @param \stdClass $cm     Course module record.
     * @param int       $userid Moodle user id of the submitting student.
     * @return int|false The group id when this is a group submission, false otherwise.
     */
    public static function check_group_submission(\stdClass $cm, int $userid) {
        global $CFG, $DB;

        $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);
        if (!empty($moduledata->teamsubmission)) {
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            $context = \context_course::instance($cm->course);

            $assignment = new \assign($context, $cm, null);
            $group = $assignment->get_submission_group($userid);

            return $group->id;
        }

        return false;
    }

    /**
     * Return the last-modified timestamp for a text_content submission from the DB.
     *
     * Used to determine whether content has changed since the last submission to
     * Turnitin — if timemodified <= lastmodified the submission is skipped.
     * Returns 0 for file submissions (timemodified comes from the stored_file
     * object, not a DB record) and when the record cannot be found.
     *
     * @param \stdClass $cm             Course module record.
     * @param string    $submissiontype One of 'file', 'text_content', etc.
     * @param int       $userid         Moodle user id (0 for group submissions).
     * @param int       $itemid         assign_submission id or workshop itemid.
     * @return int Unix timestamp, or 0 when not applicable / not found.
     */
    public static function get_content_timemodified(\stdClass $cm, string $submissiontype, int $userid, int $itemid): int {
        global $DB;

        if ($submissiontype !== 'text_content') {
            return 0;
        }

        switch ($cm->modname) {
            case 'assign':
                $record = $DB->get_record(
                    'assign_submission',
                    ['assignment' => $cm->instance, 'userid' => $userid, 'id' => $itemid],
                    'timemodified'
                );
                break;
            case 'workshop':
                $record = $DB->get_record(
                    'workshop_submissions',
                    ['workshopid' => $cm->instance, 'authorid' => $userid],
                    'timemodified'
                );
                break;
            default:
                return 0;
        }

        return $record ? (int)$record->timemodified : 0;
    }

    /**
     * Determine the submission id and Turnitin external id for a queued submission.
     *
     * Encapsulates the routing logic that decides whether to create a new row,
     * reset an existing row, or return early (no re-queue needed). The result is
     * an associative array:
     *   - earlyreturn   (bool)       — true when no further processing is needed
     *   - submissionid  (int)        — the plagiarism_turnitin_files row id to use
     *   - tiisubmissionid (string|null) — Turnitin UUID to carry forward, or null
     *   - attempt       (int)        — current attempt count from the existing row
     *
     * $timemodified is accepted as a parameter (rather than read from the file
     * storage directly) to keep the method independently testable.
     *
     * @param \stdClass $cm             Course module record.
     * @param int       $author         Moodle user id of the submission author.
     * @param string    $submissiontype One of 'file', 'text_content', 'forum_post', 'quiz_answer'.
     * @param string    $identifier     Pathnamehash or content SHA1.
     * @param array     $settings       Per-CM plagiarism settings from turnitin_settings::for_cm().
     * @param \stdClass $moduledata     Module record augmented with resubmission_allowed flag.
     * @param int|null  $timemodified   Last-modified timestamp of the content; defaults to time().
     * @return array{earlyreturn: bool, submissionid: int, tiisubmissionid: string|null, attempt: int}
     */
    public static function resolve_submission_id(
        \stdClass $cm,
        int $author,
        string $submissiontype,
        string $identifier,
        array $settings,
        \stdClass $moduledata,
        ?int $timemodified = null
    ): array {
        global $CFG, $DB;

        $timemodified    = $timemodified ?? time();
        $submissionid    = 0;
        $tiisubmissionid = null;
        $attempt         = 0;

        $submissionfields = 'id, cm, externalid, identifier, statuscode, lastmodified, attempt';
        $typefield        = ($CFG->dbtype === 'oci') ? ' to_char(submissiontype) ' : ' submissiontype ';

        switch ($submissiontype) {
            case 'file':
            case 'text_content':
                // Check if this exact content/file has been submitted previously.
                $previoussubmissions = $DB->get_records_select(
                    'plagiarism_turnitin_files',
                    ' cm = ? AND userid = ? AND ' . $typefield . ' = ? AND identifier = ?',
                    [$cm->id, $author, $submissiontype, $identifier],
                    'id',
                    $submissionfields
                );
                $previoussubmission = end($previoussubmissions);

                if ($previoussubmission) {
                    // Skip requeue when content has not changed since last submission.
                    if ($timemodified <= $previoussubmission->lastmodified) {
                        return ['earlyreturn' => true, 'submissionid' => 0, 'tiisubmissionid' => null, 'attempt' => 0];
                    }

                    if ($moduledata->resubmission_allowed) {
                        $submissionid    = $previoussubmission->id;
                        $tiisubmissionid = $previoussubmission->externalid;
                        self::reset($cm, $author, $identifier, $previoussubmission, $submissiontype);
                    } else if ($previoussubmission->statuscode !== 'success') {
                        $submissionid = $previoussubmission->id;
                        self::reset($cm, $author, $identifier, $previoussubmission, $submissiontype);
                    } else {
                        // Successful previous submission — create a fresh row so the new content gets its own record.
                        $submissionid    = self::create_new($cm, $author, $identifier, $submissiontype);
                        $tiisubmissionid = $previoussubmission->externalid;
                    }
                    $attempt = $previoussubmission->attempt;
                } else {
                    // No previous submission for this identifier — check for a different-content previous submission.
                    $previoussubmission = $DB->get_record_select(
                        'plagiarism_turnitin_files',
                        ' cm = ? AND userid = ? AND ' . $typefield . ' = ?',
                        [$cm->id, $author, $submissiontype],
                        'id, cm, externalid, identifier, statuscode, lastmodified, attempt'
                    );

                    if ($previoussubmission) {
                        $submissionid = $previoussubmission->id;
                        $attempt      = $previoussubmission->attempt;

                        // Delete old text_content from Turnitin when report_gen=0 (no resubmission mode).
                        if (
                            $submissiontype === 'text_content' &&
                            $settings['plagiarism_report_gen'] == 0 &&
                            !is_null($previoussubmission->externalid)
                        ) {
                            self::delete($cm, $previoussubmission->externalid, $author);
                        }

                        if ($moduledata->resubmission_allowed || $submissiontype === 'text_content') {
                            self::reset($cm, $author, $identifier, $previoussubmission, $submissiontype);
                            $tiisubmissionid = $previoussubmission->externalid;
                        } else {
                            $submissionid = self::create_new($cm, $author, $identifier, $submissiontype);
                        }
                    } else {
                        $submissionid = self::create_new($cm, $author, $identifier, $submissiontype);
                    }
                }
                break;

            case 'forum_post':
            case 'quiz_answer':
                $previoussubmissions = $DB->get_records_select(
                    'plagiarism_turnitin_files',
                    ' cm = ? AND userid = ? AND identifier = ? ',
                    [$cm->id, $author, $identifier],
                    'id DESC',
                    'id, cm, externalid, identifier, statuscode, attempt',
                    0,
                    1
                );

                if ($previoussubmissions) {
                    $previoussubmission = current($previoussubmissions);
                    if ($previoussubmission->statuscode === 'success') {
                        return ['earlyreturn' => true, 'submissionid' => 0, 'tiisubmissionid' => null, 'attempt' => 0];
                    }
                    $submissionid    = $previoussubmission->id;
                    $attempt         = $previoussubmission->attempt;
                    $tiisubmissionid = $previoussubmission->externalid;
                    self::reset($cm, $author, $identifier, $previoussubmission, $submissiontype);
                } else {
                    $submissionid = self::create_new($cm, $author, $identifier, $submissiontype);
                }
                break;
        }

        return [
            'earlyreturn'     => false,
            'submissionid'    => $submissionid,
            'tiisubmissionid' => $tiisubmissionid,
            'attempt'         => $attempt,
        ];
    }

    /**
     * Validate a file for submission to Turnitin and return the appropriate errorcode.
     *
     * Returns 0 when the file passes all checks, 2 when it exceeds the maximum
     * upload size, or 4 when the extension is not accepted (and acceptanyfiletype
     * is false). Size is checked before extension so that errorcode 2 takes
     * precedence over errorcode 4 for oversized files with bad extensions.
     *
     * @param \stored_file $file              The Moodle stored file to validate.
     * @param bool         $acceptanyfiletype True when the assignment allows any file type.
     * @param string[]     $acceptedfiles     List of allowed extensions, e.g. ['.pdf', '.docx'].
     * @return int 0 = valid, 2 = too large, 4 = unsupported extension.
     */
    public static function get_file_errorcode(\stored_file $file, bool $acceptanyfiletype, array $acceptedfiles): int {
        if ($file->get_filesize() > PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE) {
            return 2;
        }

        if (!$acceptanyfiletype) {
            $parts     = explode('.', $file->get_filename());
            $extension = strtolower(end($parts));
            if (!in_array('.' . $extension, $acceptedfiles)) {
                return 4;
            }
        }

        return 0;
    }

    /**
     * Compute the SHA1 identifier for a text_content or forum_post submission.
     *
     * The hash encodes different fields depending on the module type to ensure
     * identifiers are stable and unique across users, activities, and attempts:
     *   - forum:  user + cm + content  (per-user per-activity per-message)
     *   - assign: cm + itemid + content (per-activity per-attempt)
     *   - other:  content only
     *
     * @param \stdClass $cm      Course module record.
     * @param int       $author  Moodle user id of the submission author.
     * @param string    $content Raw text content of the submission.
     * @param int       $itemid  Submission/post id (used for assign).
     * @return string 40-character hex SHA1 hash.
     */
    public static function calculate_content_identifier(\stdClass $cm, int $author, string $content, int $itemid): string {
        switch ($cm->modname) {
            case 'forum':
                return sha1('forum_post user' . $author . ' cm' . $cm->id . ' ' . $content);
            case 'assign':
                return sha1('text_content cm' . $cm->id . ' itemid' . $itemid . ' ' . $content);
            default:
                return sha1($content);
        }
    }

    /**
     * Delete queued submission records for a specific submission.
     *
     * Called when a submission is removed from Moodle (submission_removed event)
     * to prevent the cron from attempting to send a submission that no longer exists.
     * Only removes records with statuscode='queued' — already-processed records are preserved.
     *
     * @param int $cmid   Course module id.
     * @param int $userid Moodle user id.
     * @param int $itemid The submission item id.
     */
    public static function remove_queued_for_submission(int $cmid, int $userid, int $itemid): void {
        global $DB;

        $DB->delete_records('plagiarism_turnitin_files', [
            'cm'         => $cmid,
            'userid'     => $userid,
            'itemid'     => $itemid,
            'statuscode' => 'queued',
        ]);
    }

    /**
     * Determine the submission author from event data.
     *
     * Normally the author is the relateduserid (the student whose work is being
     * submitted). When relateduserid is absent the submitter is the author —
     * this covers self-submissions and non-assign modules.
     *
     * Note: the group-submission edge case (instructor submitting on behalf of a
     * group member) requires capability checks and DB access so is handled by
     * the caller after this method returns.
     *
     * @param array     $eventdata Event data array from the plagiarism event.
     * @param \stdClass $cm        Course module record (used to determine module type).
     * @return int Moodle user id of the submission author.
     */
    public static function resolve_author(array $eventdata, \stdClass $cm): int {
        return (!empty($eventdata['relateduserid']))
            ? (int)$eventdata['relateduserid']
            : (int)$eventdata['userid'];
    }

    /**
     * Enrich event data for an assessable_submitted event with the actual
     * text content and file pathnamehashes from the database.
     *
     * For assessable_submitted events the content and file list are not included
     * in the event data itself — they must be fetched from the assignment submission
     * tables. Returns the modified $eventdata array with 'content' and
     * 'pathnamehashes' populated under the 'other' key.
     *
     * @param array $eventdata The event data array (passed by value — caller receives enriched copy).
     * @param int   $author    Moodle user id of the submission author.
     * @return array The enriched event data array.
     */
    public static function enrich_assessable_submitted(array $eventdata, int $author): array {
        global $DB;

        $moodlesubmission = $DB->get_record('assign_submission', ['id' => $eventdata['objectid']], 'id');

        // Populate online text content when present.
        if (
            $moodletextsubmission = $DB->get_record(
                'assignsubmission_onlinetext',
                ['submission' => $moodlesubmission->id],
                'onlinetext'
            )
        ) {
            $eventdata['other']['content'] = $moodletextsubmission->onlinetext;
        }

        // Collect pathnamehashes for any files attached to this submission.
        $eventdata['other']['pathnamehashes'] = [];
        $filesconditions = [
            'component' => 'assignsubmission_file',
            'itemid'    => $moodlesubmission->id,
            'userid'    => $author,
        ];
        if ($moodlefiles = $DB->get_records('files', $filesconditions)) {
            foreach ($moodlefiles as $moodlefile) {
                $eventdata['other']['pathnamehashes'][] = $moodlefile->pathnamehash;
            }
        }

        return $eventdata;
    }

    /**
     * Determine the Turnitin submission type for a text-based submission.
     *
     * Forum posts are a distinct type because they are stored and retrieved
     * differently from generic text content submissions.
     *
     * @param string $modname Moodle module name (e.g. 'forum', 'assign', 'workshop').
     * @return string 'forum_post' for forum modules, 'text_content' for all others.
     */
    public static function get_submission_type(string $modname): string {
        return $modname === 'forum' ? 'forum_post' : 'text_content';
    }

    /**
     * Retrieve the canonical text content for a submission from the database.
     *
     * Event data content is not always reliable — for workshop and forum
     * submissions, URLs may have been rewritten (e.g. @@PLUGINFILE@@) between
     * the event being fired and this handler running, causing hash mismatches.
     * This method fetches the authoritative content directly from the DB.
     *
     * Returns $content unchanged for module types that don't need a DB lookup.
     *
     * @param \stdClass $cm       Course module record.
     * @param int       $objectid The submission/post id.
     * @param string    $content  The content from the event data (fallback for unrecognised modules).
     * @return string The canonical content string.
     */
    public static function get_normalised_content(\stdClass $cm, int $objectid, string $content): string {
        global $DB;

        switch ($cm->modname) {
            case 'workshop':
                $record = $DB->get_record('workshop_submissions', ['id' => $objectid]);
                return $record ? $record->content : $content;
            case 'forum':
                $record = $DB->get_record('forum_posts', ['id' => $objectid]);
                return $record ? $record->message : $content;
            default:
                return $content;
        }
    }

    /**
     * Check whether a stored file can be submitted to Turnitin.
     *
     * Returns false for Moodle's directory placeholder files (filename = '.')
     * which represent empty directories rather than real content, and for files
     * whose content cannot be read (e.g. the file record exists but the data
     * file is missing from the file store).
     *
     * @param \stored_file $file The file to check.
     * @return bool True when the file is a real, readable file.
     */
    public static function is_file_submittable(\stored_file $file): bool {
        if ($file->get_filename() === '.') {
            return false;
        }

        try {
            $fh = $file->get_content_file_handle();
            fclose($fh);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the gradebook should be updated for a given submission and,
     * if so, trigger the grade update.
     *
     * This is the grade-update callback passed to turnitin_submission::update(). It
     * was previously an anonymous closure inside plagiarism_plugin_turnitin::update_submission().
     *
     * The logic skips gradebook updates when:
     * - The submission is not the latest attempt (file or text-content on assign).
     * - The module type is coursework (handled externally).
     * - The grade is null, the grade item does not exist, or a grade update is not required.
     *
     * @param \stdClass  $cm            Course module record.
     * @param int        $submissionid  Row id in plagiarism_turnitin_files.
     * @param object     $tiisubmission Turnitin TiiSubmission SDK object.
     * @param int        $userid        Moodle user id of the submission author.
     * @param callable|null $gradeupdater Callable that performs the actual gradebook update;
     *                    injected in tests to avoid gradebook API calls. When null the real
     *                    plagiarism_plugin_turnitin::update_grade() is invoked via $plugin.
     * @param \plagiarism_plugin_turnitin|null $plugin Plugin instance used when $gradeupdater
     *                    is null. Defaults to a fresh instance.
     * @return bool True on success or when no update was needed, false on failure.
     */
    public static function update_gradebook(
        \stdClass $cm,
        int $submissionid,
        object $tiisubmission,
        int $userid,
        ?callable $gradeupdater = null,
        ?\plagiarism_plugin_turnitin $plugin = null
    ): bool {
        global $DB;

        $gbupdaterequired = true;
        $submissiondata   = $DB->get_record(
            'plagiarism_turnitin_files',
            ['id' => $submissionid],
            'identifier, submissiontype, userid, itemid'
        );

        if (!$submissiondata) {
            return true;
        }

        if ($cm->modname === 'assign') {
            if ($submissiondata->submissiontype === 'file') {
                $fs = get_file_storage();
                if ($file = $fs->get_file_by_hash($submissiondata->identifier)) {
                    $itemid         = $file->get_itemid();
                    $assignmentdata = ['assignment' => $cm->instance];
                    $groupid        = self::check_group_submission($cm, $submissiondata->userid);
                    if ($groupid) {
                        $assignmentdata['groupid'] = $groupid;
                    } else {
                        $assignmentdata['userid'] = $submissiondata->userid;
                    }
                    $submission = $DB->get_records(
                        'assign_submission',
                        $assignmentdata,
                        'id DESC',
                        'id, attemptnumber',
                        '0',
                        '1'
                    );
                    $item = current($submission);
                    if ($item->id != $itemid) {
                        $gbupdaterequired = false;
                    }
                } else {
                    $gbupdaterequired = false;
                }
            } else if ($submissiondata->submissiontype === 'text_content') {
                $moduleobject = new \plagiarism_turnitin\modules\turnitin_assign();
                $latesttext   = $moduleobject->get_onlinetext($submissiondata->userid, $cm);
                if (!empty($latesttext)) {
                    $latestidentifier    = sha1(
                        'text_content cm' . $cm->id . ' itemid' . $latesttext->itemid
                            . ' ' . $latesttext->onlinetext
                    );
                    $oldlatestidentifier = sha1($latesttext->onlinetext);
                    if (
                        $submissiondata->identifier !== $latestidentifier
                            && $submissiondata->identifier !== $oldlatestidentifier
                    ) {
                        $gbupdaterequired = false;
                    }
                }
            }
        }

        if ($cm->modname === 'coursework') {
            return true;
        }

        if ($cm->modname === 'quiz') {
            $quiz           = $DB->get_record('quiz', ['id' => $cm->instance]);
            $plagiarismfile = $DB->get_record('plagiarism_turnitin_files', ['id' => $submissionid], 'grade');
            $tq             = new \plagiarism_turnitin\modules\turnitin_quiz();
            if (!is_null($plagiarismfile->grade)) {
                $tq->update_mark(
                    $submissiondata->itemid ?? 0,
                    $submissiondata->identifier,
                    $userid,
                    $plagiarismfile->grade,
                    $quiz->grade
                );
            }
            return true;
        }

        $gradeitem      = $DB->get_record(
            'grade_items',
            ['iteminstance' => $cm->instance, 'itemmodule' => $cm->modname,
             'courseid' => $cm->course, 'itemnumber' => 0]
        );
        $plagiarismfile = $DB->get_record('plagiarism_turnitin_files', ['id' => $submissionid], 'grade');

        if (!is_null($plagiarismfile->grade) && !empty($gradeitem) && $gbupdaterequired) {
            if ($gradeupdater !== null) {
                return $gradeupdater($cm, $tiisubmission, $userid);
            }
            $pluginturnitin = $plugin ?? new \plagiarism_plugin_turnitin();
            return $pluginturnitin->update_grade($cm, $tiisubmission, $userid);
        }

        return true;
    }

    /**
     * Resolve the content identifier, old identifier, item id, and submission type
     * for a submission being displayed in get_links().
     *
     * Returns a stdClass with four properties:
     *   - identifier    string  Primary SHA-1 hash used to look up the submission.
     *   - oldidentifier string  Legacy hash for backwards-compatible lookup (may equal identifier).
     *   - itemid        int     Moodle assign_submission id (0 for non-file/non-assign).
     *   - submissiontype string 'file' | 'text_content' | 'forum_post' | 'quiz_answer'
     *
     * @param array  $linkarray   The linkarray passed to get_links().
     * @param \stdClass $cm       Course module record (needs ->modname, ->id).
     * @param \stdClass $file     The stored_file object when linkarray['file'] is set, or null.
     * @param object $moduleobject Module-specific object (e.g. turnitin_assign) for get_onlinetext().
     * @return \stdClass
     */
    public static function resolve_content_identifier(
        array $linkarray,
        \stdClass $cm,
        ?object $file,
        object $moduleobject
    ): \stdClass {
        $result              = new \stdClass();
        $result->identifier  = '';
        $result->oldidentifier = '';
        $result->itemid      = 0;
        $result->submissiontype = '';

        if (!empty($linkarray['file']) && $file !== null) {
            $result->identifier     = $file->get_pathnamehash();
            $result->itemid         = $file->get_itemid();
            $result->submissiontype = 'file';
        } else if (!empty($linkarray['content'])) {
            $result->submissiontype = 'text_content';
            if ($cm->modname === 'forum') {
                $result->submissiontype = 'forum_post';
            } else if ($cm->modname === 'quiz') {
                $result->submissiontype = 'quiz_answer';
            }

            $content = $linkarray['content'];

            if ($result->submissiontype === 'quiz_answer') {
                $attempt = \mod_quiz\quiz_attempt::create_from_usage_id($linkarray['area']);
                $result->identifier    = sha1(
                    'quiz_attempt user' . $attempt->get_userid() . ' cm' . $cm->id .
                    ' slot' . $linkarray['itemid'] . ' attempt' . $attempt->get_attempt_number()
                );
                $result->oldidentifier = sha1($content . $linkarray['itemid']);
            } else if ($result->submissiontype === 'forum_post') {
                $result->identifier    = sha1('forum_post user' . $linkarray['userid'] . ' cm' . $cm->id . ' ' . $content);
                $result->oldidentifier = sha1($content);
            } else if ($cm->modname === 'assign') {
                $result->itemid        = $moduleobject->get_onlinetext($linkarray['userid'], $cm)->itemid;
                $result->identifier    = sha1('text_content cm' . $cm->id . ' itemid' . $result->itemid . ' ' . $content);
                $result->oldidentifier = sha1($content);
            } else {
                $result->identifier = sha1($content);
            }
        }

        return $result;
    }

    /**
     * Resolve the set of user IDs who are allowed to view a submission's originality links.
     *
     * For individual submissions this is just [$userid]. For group submissions on assign
     * (teamsubmission=1) or coursework (use_groups=1) it expands to all group members so
     * every member can see the score even though only one person submitted.
     *
     * @param \stdClass $cm         Course module record (needs ->modname, ->id).
     * @param \stdClass $moduledata Module record (e.g. assign, coursework).
     * @param int       $userid     The submitting user's Moodle id.
     * @param \context  $context    Course context (used by assign::get_submission_group).
     * @return int[] Array of Moodle user IDs who may view the submission links.
     */
    public static function resolve_submission_users(
        \stdClass $cm,
        \stdClass $moduledata,
        int $userid,
        \context $context
    ): array {
        global $DB;

        $submissionusers = [$userid];

        switch ($cm->modname) {
            case 'assign':
                if (!empty($moduledata->teamsubmission)) {
                    $assignment = new \assign($context, $cm, null);
                    if ($group = $assignment->get_submission_group($userid)) {
                        $users           = groups_get_members($group->id);
                        $submissionusers = array_keys($users);
                    }
                }
                break;

            case 'coursework':
                if (!empty($moduledata->use_groups)) {
                    $coursework = new \mod_coursework\models\coursework($moduledata->id);
                    $user       = $DB->get_record('user', ['id' => $userid]);
                    $user       = \mod_coursework\models\user::find($user);
                    if ($group = $coursework->get_student_group($user)) {
                        $users           = groups_get_members($group->id);
                        $submissionusers = array_keys($users);
                    }
                }
                break;
        }

        return $submissionusers;
    }

    /**
     * Find the first student (non-grader) in a group for a group assignment submission.
     *
     * When an instructor submits on behalf of a group, the relateduserid is absent. This
     * method resolves the correct author by iterating group members and returning the first
     * one who does not have the mod/assign:grade capability — i.e. the first student.
     *
     * @param int $courseid Moodle course id (used to build the course context).
     * @param int $groupid  Moodle group id.
     * @return int|null Moodle user id of the first non-grader group member, or null when
     *                  the group is empty or contains only graders.
     */
    public static function get_first_group_author(int $courseid, int $groupid): ?int {
        $context      = \context_course::instance($courseid);
        $groupmembers = groups_get_members($groupid, 'u.id');

        foreach ($groupmembers as $member) {
            if (!has_capability('mod/assign:grade', $context, $member->id)) {
                return (int)$member->id;
            }
        }

        return null;
    }

    /**
     * Return true when a file submission should be silently skipped because its
     * filearea is never submitted to Turnitin (e.g. feedback files, intro attachments).
     *
     * @param \stored_file $file The Moodle stored_file object from linkarray['file'].
     * @return bool
     */
    public static function should_skip_non_submitting_filearea(\stored_file $file): bool {
        $nonsubmittingareas = ['feedback_files', 'introattachment'];
        return in_array($file->get_filearea(), $nonsubmittingareas);
    }

    /**
     * Return true when the quiz component is present but quizzes are disabled in Turnitin.
     *
     * @param string $component Value of linkarray['component'] (e.g. 'qtype_essay' or '').
     * @return bool
     */
    public static function should_skip_quiz_disabled(string $component): bool {
        return $component === 'qtype_essay'
            && empty(turnitin_settings::module_enabled('mod_quiz'));
    }

    /**
     * Resolve the correct author and update the userid in $linkarray.
     *
     * When an instructor submits on behalf of a group the plagiarism_turnitin_files row
     * carries the real student author; otherwise the module object resolves the author
     * from the submission itemid.
     *
     * Returns a stdClass with:
     *   - author  int|null  Resolved Moodle user id of the actual submission author.
     *   - plagiarismfile \stdClass|null  Pre-fetched plagiarism_turnitin_files row (for
     *                     group submissions), or null when not yet fetched.
     *   - userid  int  The resolved userid to use going forward (may differ from the
     *                  original linkarray value for group/instructor-submitted cases).
     *
     * @param array   $linkarray   The current linkarray (userid, cmid, etc.).
     * @param \stdClass $cm        Course module record.
     * @param int     $itemid      The assign_submission itemid from resolve_content_identifier.
     * @param string  $identifier  The submission identifier hash.
     * @param object  $moduleobject Module-specific object (e.g. turnitin_assign).
     * @return \stdClass
     */
    public static function resolve_get_links_author(
        array $linkarray,
        \stdClass $cm,
        int $itemid,
        string $identifier,
        object $moduleobject
    ): \stdClass {
        global $DB;

        $result               = new \stdClass();
        $result->plagiarismfile = null;
        $result->author       = $linkarray['userid'];
        $result->userid       = $linkarray['userid'];

        $moodlesubmission = $DB->get_record('assign_submission', ['id' => $itemid], 'id, groupid');

        if (!empty($moodlesubmission->groupid) && $cm->modname === 'assign') {
            $plagiarismfiles = $DB->get_records(
                'plagiarism_turnitin_files',
                ['itemid' => $itemid, 'cm' => $cm->id, 'identifier' => $identifier],
                'lastmodified DESC',
                '*',
                0,
                1
            );
            $result->plagiarismfile = reset($plagiarismfiles) ?: null;
            $result->author         = $result->plagiarismfile->userid ?? null;
            $result->userid         = $result->author;
        } else {
            if ($itemid !== 0) {
                $author         = $moduleobject->get_author($itemid);
                $result->author = $author;
                $result->userid = !empty($author) ? $author : $linkarray['userid'];
            }
        }

        return $result;
    }

    /**
     * Determine whether grades have been released for viewing on the submission page.
     *
     * Rules:
     * - No grade item → grades released (default true).
     * - Grade item hidden=1 → not released.
     * - Grade item hidden=<timestamp> >= now → not released (hidden until date).
     * - Grade item hidden=0 → released (unless marking workflow overrides).
     * - Assign marking workflow: released only when the specific user's flag = 'released'.
     *
     * @param \stdClass      $cm          Course module record.
     * @param \stdClass      $moduledata  Module record.
     * @param int            $userid      Submitting user's Moodle id.
     * @param \stdClass|null $gradeitem   Grade items row, or null when absent.
     * @return bool
     */
    public static function resolve_grades_released(
        \stdClass $cm,
        \stdClass $moduledata,
        int $userid,
        ?\stdClass $gradeitem
    ): bool {
        global $DB;

        if ($gradeitem === null) {
            return true;
        }

        switch ($gradeitem->hidden) {
            case 1:
                $gradesreleased = false;
                break;
            default:
                $gradesreleased = ($gradeitem->hidden >= time()) ? false : true;
                break;
        }

        // Marking workflow overrides the gradebook hidden date.
        if ($cm->modname === 'assign' && !empty($moduledata->markingworkflow)) {
            $gradesreleased = $DB->record_exists(
                'assign_user_flags',
                ['userid' => $userid, 'assignment' => $cm->instance, 'workflowstate' => 'released']
            );
        }

        return $gradesreleased;
    }

    /**
     * Check whether the submission author has accepted the Turnitin EULA.
     *
     * Only relevant when there is no plagiarism_turnitin_files row yet (the submission
     * has not been sent to Turnitin). In that case tutors viewing a student's work need
     * to know whether to prompt the student to accept.
     *
     * Returns true in all other cases (no check needed).
     *
     * @param bool      $hasplagiarismfile    Whether a plagiarism_turnitin_files row exists.
     * @param int       $submissionuserid     The resolved submission author userid.
     * @param int       $vieweruserid         The current viewer's userid ($USER->id).
     * @param int       $submittinguser       The original submitter from the linkarray.
     * @param int|null  $author               The resolved author after group lookup.
     * @param bool      $istutor              Whether the viewer is a tutor.
     * @param \context  $context              Course context (for enrolment check).
     * @param object    $moduleobject         Module-specific object.
     * @param turnitin_user|null $tiiuser     Injected turnitin_user; constructed when null.
     * @return bool
     */
    public static function resolve_submitter_eula_accepted(
        bool $hasplagiarismfile,
        int $submissionuserid,
        int $vieweruserid,
        int $submittinguser,
        ?int $author,
        bool $istutor,
        \context $context,
        object $moduleobject,
        ?turnitin_user $tiiuser = null
    ): bool {
        global $DB;

        if ($hasplagiarismfile) {
            return true;
        }

        if ($submissionuserid === $vieweruserid) {
            return true;
        }

        if ($submittinguser !== $author || !$istutor) {
            return true;
        }

        if (!$DB->get_record('user', ['id' => $submissionuserid])) {
            return true;
        }

        if (!$moduleobject->user_enrolled_on_course($context, $submissionuserid)) {
            return true;
        }

        $user = $tiiuser ?? new turnitin_user($submissionuserid, 'Learner');
        return ($user->useragreementaccepted == 1);
    }

    /**
     * Assemble a submission_link_context object from all resolved values.
     *
     * @param array      $linkarray
     * @param \stdClass  $cm
     * @param \stdClass  $config            Plugin admin config.
     * @param array      $plagiarismsettings Per-CM settings from turnitin_settings::for_cm().
     * @param \stdClass|null $plagiarismfile Row from plagiarism_turnitin_files, or null.
     * @param string     $submissiontype
     * @param array      $submissionusers
     * @param bool       $istutor
     * @param bool       $isnonsubmitterforgroupassign
     * @param bool       $gradesreleased
     * @param bool       $gradeexists
     * @param bool       $blindon
     * @param \stdClass|null $gradeitem
     * @param array      $peermarkassignments
     * @param bool       $submittereulaccepted
     * @param string     $wwwroot
     * @param int        $filesize
     * @return submission_link_context
     */
    public static function build_submission_link_context(
        array $linkarray,
        \stdClass $cm,
        \stdClass $config,
        array $plagiarismsettings,
        ?\stdClass $plagiarismfile,
        string $submissiontype,
        array $submissionusers,
        bool $istutor,
        bool $isnonsubmitterforgroupassign,
        bool $gradesreleased,
        bool $gradeexists,
        bool $blindon,
        ?\stdClass $gradeitem,
        array $peermarkassignments,
        bool $submittereulaccepted,
        string $wwwroot,
        int $filesize
    ): submission_link_context {
        $ctx = new submission_link_context();
        $ctx->plagiarismfile               = $plagiarismfile;
        $ctx->istutor                      = $istutor;
        $ctx->vieweruserid                 = $linkarray['userid'] ?? 0;
        $ctx->submissionuserid             = $linkarray['userid'] ?? 0;
        $ctx->submissionusers              = $submissionusers;
        $ctx->isnonsubmitterforgroupassign = $isnonsubmitterforgroupassign;
        $ctx->submissiontype               = $submissiontype;
        $ctx->cmid                         = $linkarray['cmid'] ?? 0;
        $ctx->cmmodname                    = $cm->modname;
        $ctx->cmcourse                     = $cm->course;
        $ctx->wwwroot                      = $wwwroot;
        $ctx->submissioncontent            = $linkarray['content'] ?? null;
        $ctx->filesize                     = $filesize;
        $ctx->usegrademark                 = !empty($config->plagiarism_turnitin_usegrademark);
        $ctx->enablepeermark               = !empty($config->plagiarism_turnitin_enablepeermark);
        $ctx->showstudentreport            = !empty($plagiarismsettings['plagiarism_show_student_report']);
        $ctx->rubric                       = $plagiarismsettings['plagiarism_rubric'] ?? null;
        $ctx->gradesreleased               = $gradesreleased;
        $ctx->blindon                      = $blindon;
        $ctx->gradeexists                  = $gradeexists;
        $ctx->gradeitem                    = $gradeitem;
        $ctx->peermarkassignments          = $peermarkassignments;
        $ctx->submittereulaccepted         = $submittereulaccepted;
        return $ctx;
    }
}

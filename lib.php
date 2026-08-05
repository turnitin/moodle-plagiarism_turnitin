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
 * Turnitin lib file
 *
 * @package   plagiarism_turnitin
 * @copyright 2013 iParadigms LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Integrations\PhpSdk\TiiClass;
use Integrations\PhpSdk\TiiSubmission;
use Integrations\PhpSdk\TiiAssignment;
use Integrations\PhpSdk\TiiLTI;

defined('MOODLE_INTERNAL') || die();

// phpcs:ignore moodle.Commenting.InlineComment.SpacingBefore

// Constants.
define('PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE', 104857600);
define('PLAGIARISM_TURNITIN_NUM_RECORDS_RETURN', 500);
define('PLAGIARISM_TURNITIN_CRON_SUBMISSIONS_LIMIT', 100);
define('PLAGIARISM_TURNITIN_REPORT_GEN_SPEED_NUM_RESUBMISSIONS', 3);
define('PLAGIARISM_TURNITIN_REPORT_GEN_SPEED_NUM_HOURS', 24);
define('PLAGIARISM_TURNITIN_MAX_FILENAME_LENGTH', 180);
define('PLAGIARISM_TURNITIN_COURSE_TITLE_LIMIT', 300);

// Admin Repository constants.
define('PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_STANDARD', 0);
define('PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_EXPANDED', 1);
define('PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD', 2);
define('PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_NO', 3);
define('PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_INSTITUTIONAL', 4);

// Submit Papers to Repository constants.
define('PLAGIARISM_TURNITIN_SUBMIT_TO_NO_REPOSITORY', 0);
define('PLAGIARISM_TURNITIN_SUBMIT_TO_STANDARD_REPOSITORY', 1);
define('PLAGIARISM_TURNITIN_SUBMIT_TO_INSTITUTIONAL_REPOSITORY', 2);

// Student privacy constants.
define('PLAGIARISM_TURNITIN_DEFAULT_PSEUDO_DOMAIN', '@tiimoodle.com');
define('PLAGIARISM_TURNITIN_DEFAULT_PSEUDO_FIRSTNAME', get_string('defaultcoursestudent'));

// Define accepted files if the module is not accepting any file type.
global $turnitinacceptedfiles;
$turnitinacceptedfiles = ['.doc', '.docx', '.ppt', '.pptx', '.pps', '.ppsx',
                                '.pdf', '.txt', '.htm', '.html', '.hwp', '.odt',
                                '.wpd', '.ps', '.rtf', '.xls', '.xlsx', ];

require_once($CFG->libdir . '/gradelib.php');

// Get global class.
require_once($CFG->dirroot . '/plagiarism/lib.php');

// Get helper methods.
require_once($CFG->dirroot . '/plagiarism/turnitin/locallib.php');

// Classes in classes/ are autoloaded by Moodle's PSR-4 autoloader.

/**
 * Class plagiarism_plugin_turnitin
 */
class plagiarism_plugin_turnitin extends plagiarism_plugin {
    /**
     * @var bool Static variable to load the function from js files by using js_call_amd only once.
     */
    private static $amdcomponentsloaded = false;

    /**
     * Get a list of the file upload errors.
     *
     * @param int $offset Number of records to skip.
     * @param int $limit  Max records to return.
     * @param bool $count If true, returns a count of the total number of
     *                    records.
     * @return array|int A list of records, or count when $count is true.
     */
    public function get_file_upload_errors($offset = 0, $limit = 0, $count = false) {
        global $DB;

        $sql = "FROM {plagiarism_turnitin_files} PTF
                LEFT JOIN {user} U ON U.id = PTF.userid
                LEFT JOIN {course_modules} CM ON CM.id = PTF.cm
                LEFT JOIN {modules} M ON CM.module = M.id
                LEFT JOIN {course} C ON CM.course = C.id
                WHERE PTF.statuscode = 'error'";
        $countsql = "SELECT count(1) $sql";
        $selectsql = "SELECT PTF.id, U.firstname, U.lastname, U.email, PTF.cm, M.name AS moduletype,
                            C.id AS courseid, C.fullname AS coursename, PTF.identifier, PTF.submissiontype,
                            PTF.errorcode, PTF.errormsg
                      $sql
                      ORDER BY PTF.id DESC";

        if ($count) {
            return $DB->count_records_sql($countsql);
        }
        return $DB->get_records_sql($selectsql, [], $offset, $limit);
    }

    /**
     * This function is called from the inbox in mod assign.
     * This will alert the user to refresh the assignment when there has been a change in scores.
     *
     * @param object $course The course object
     * @param object $cm The course module.
     * @return string
     */
    public function update_status($course, $cm) {
        return html_writer::div(
            get_string('turnitin_score_refresh_alert', 'plagiarism_turnitin'),
            'turnitin_score_refresh_alert',
            ['id' => 'turnitin_score_refresh_alert']
        );
    }

    /**
     * Test whether we can connect to Turnitin.
     *
     * Initially only being used if a student is logged in before checking whether they have accepted the EULA.
     *
     * @param string $workflowcontext The context of the workflow
     */
    public function test_turnitin_connection($workflowcontext = 'site') {
        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $tiiapi = $turnitincomms->initialise_api();

        $class = new TiiClass();
        $class->setTitle('Test finding a class to see if connection works');

        try {
            $tiiapi->findClasses($class);
            return true;
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'connecttesterror', false);
            if ($workflowcontext == 'cron') {
                mtrace(get_string('ppeventsfailedconnection', 'plagiarism_turnitin'));
            }
            return false;
        }
    }

    /**
     * Print the Turnitin student disclosure inside the submission page for students to see
     * This is a hook that's part of the Moodle plagiarism API and will be called by Moodle.
     *
     * @param int $cmid The course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        return \plagiarism_turnitin\turnitin_disclosure::render($cmid, $this);
    }

    /**
     * Load JS needed by the page.
     */
    public function load_page_components() {
        global $PAGE, $CFG;
        // The function from js files by using js_call_amd will be loaded only once.
        if (static::$amdcomponentsloaded) {
            return;
        }

        $PAGE->requires->string_for_js('turnitin_score_refresh_alert', 'plagiarism_turnitin');

        $PAGE->requires->js_call_amd('plagiarism_turnitin/open_viewer', 'origreport_open');
        $PAGE->requires->js_call_amd('plagiarism_turnitin/open_viewer', 'grademark_open');
        $PAGE->requires->js_call_amd('plagiarism_turnitin/new_eula_modal', 'newEulaLaunch');
        $PAGE->requires->js_call_amd('plagiarism_turnitin/new_peermark', 'newPeermarkLaunch');
        $PAGE->requires->js_call_amd('plagiarism_turnitin/resend_submission', 'resendSubmission');

        $PAGE->requires->string_for_js('closebutton', 'plagiarism_turnitin');
        $PAGE->requires->string_for_js('loadingdv', 'plagiarism_turnitin');
        if (!static::$amdcomponentsloaded) {
            static::$amdcomponentsloaded = true;
        }
    }

    /**
     * Get the links to display on the submission page.
     *
     * @param array $linkarray The link array
     * @return string
     */
    public function get_links($linkarray) {
        global $CFG, $DB, $OUTPUT, $USER, $PAGE;

        $PAGE->requires->js_call_amd('plagiarism_turnitin/new_rubric', 'newRubric');

        $output = "";

        // Don't show links for certain file types as they won't have been submitted to Turnitin.
        if (!empty($linkarray["file"])) {
            $file = $linkarray["file"];
            if (\plagiarism_turnitin\turnitin_submission::should_skip_non_submitting_filearea($file)) {
                return $output;
            }
        }

        $component = (!empty($linkarray['component'])) ? $linkarray['component'] : "";

        // Exit if this is a quiz and quizzes are disabled.
        if (\plagiarism_turnitin\turnitin_submission::should_skip_quiz_disabled($component)) {
            return $output;
        }

        // If this is a quiz, retrieve the cmid.
        if ($component == "qtype_essay" && !empty($linkarray['area']) && empty($linkarray['cmid'])) {
            $questions = question_engine::load_questions_usage_by_activity($linkarray['area']);

            // Try to get cm using the questions owning context.
            $context = $questions->get_owning_context();
            if (empty($linkarray['cmid']) && $context->contextlevel == CONTEXT_MODULE) {
                $linkarray['cmid'] = $context->instanceid;
            }
        }

        // Set static variables.
        static $cm;
        static $forum;
        if (empty($cm)) {
            $cm = get_coursemodule_from_id('', $linkarray["cmid"]);

            if ($cm->modname == 'forum') {
                if (! $forum = $DB->get_record("forum", ["id" => $cm->instance])) {
                    throw new \moodle_exception('invalidforumid', 'forum');
                }
            }
        }

        static $config;
        if (empty($config)) {
            $config = \plagiarism_turnitin\turnitin_settings::admin_config();
        }

        // Retrieve the plugin settings for this module.
        static $plagiarismsettings = null;
        if (is_null($plagiarismsettings)) {
            $plagiarismsettings = \plagiarism_turnitin\turnitin_settings::for_cm($linkarray["cmid"]);
        }

        // Is this plugin enabled for this activity type.
        static $moduletiienabled;
        if (empty($moduletiienabled)) {
            $moduletiienabled = \plagiarism_turnitin\turnitin_settings::module_enabled('mod_' . $cm->modname);
        }

        // Exit if Turnitin is not being used for this module or activity type.
        if (empty($moduletiienabled) || empty($plagiarismsettings['use_turnitin'])) {
            return $output;
        }

        static $moduledata;
        if (empty($moduledata)) {
            $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);
        }

        static $context;
        if (empty($context)) {
            $context = context_course::instance($cm->course);
        }

        static $coursedata;
        if (empty($coursedata)) {
            $coursedata = \plagiarism_turnitin\turnitin_course::get_course_data($cm->id, $cm->course, 'site', $this);
        }

        $isnonsubmitterforgroupassign = false;

        // Create module object.
        $moduleclass = "plagiarism_turnitin\\modules\\turnitin_" . $cm->modname;
        $moduleobject = new $moduleclass();

        // Work out if logged in user is a tutor on this activity module.
        static $istutor;
        if (empty($istutor)) {
            $ctxmodule = context_module::instance($cm->id);
            $istutor = $moduleobject->is_tutor($ctxmodule);
        }

        // Define the timestamp for updating Peermark Assignments.
        if (empty($_SESSION["updated_pm"][$cm->id]) && $config->plagiarism_turnitin_enablepeermark) {
            $_SESSION["updated_pm"][$cm->id] = (time() - (60 * 5));
        }

        // If a text submission has been made, we can only display links for current attempts so don't show links previous attempts.
        // This will need to be reworked when linkarray contains submission id.
        static $contentdisplayed;
        if ($cm->modname == 'assign' && !empty($linkarray["content"]) && $contentdisplayed == true) {
            return $output;
        }

        if ((!empty($linkarray["file"]) || !empty($linkarray["content"])) && !empty($linkarray["cmid"])) {
            $this->load_page_components();

            $submittinguser = $linkarray['userid'];

            // Group submissions where all students have to submit sets userid to 0.
            if ($linkarray['userid'] == 0 && !$istutor) {
                $linkarray['userid'] = $USER->id;
            }

            // Resolve identifier, old identifier, itemid, and submission type.
            $contentinfo    = \plagiarism_turnitin\turnitin_submission::resolve_content_identifier(
                $linkarray,
                $cm,
                $linkarray['file'] ?? null,
                $moduleobject
            );
            $identifier     = $contentinfo->identifier;
            $oldidentifier  = $contentinfo->oldidentifier;
            $itemid         = $contentinfo->itemid;
            $submissiontype = $contentinfo->submissiontype;

            // Resolve the correct author and plagiarism file for group submissions.
            $authorinfo     = \plagiarism_turnitin\turnitin_submission::resolve_get_links_author(
                $linkarray,
                $cm,
                $itemid,
                $identifier,
                $moduleobject
            );
            $plagiarismfile      = $authorinfo->plagiarismfile;
            $author              = $authorinfo->author;
            $linkarray['userid'] = $authorinfo->userid;

            // Show the EULA for a student if necessary.
            if ($linkarray["userid"] == $USER->id) {
                $eula = "";

                static $userid;
                if (empty($userid)) {
                    $userid = 0;
                }

                // Show EULA if necessary and we have a connection to Turnitin.
                if ($userid != $linkarray["userid"]) {
                    static $eulashown;
                    if (empty($eulashown)) {
                        $eulashown = false;
                    }

                    $user = new \plagiarism_turnitin\turnitin_user($USER->id, "Learner");
                    $success = $user->join_user_to_class($coursedata->turnitin_cid);

                    // Variable $success is false if there is no Turnitin connection and null if user has previously been enrolled.
                    if ((is_null($success) || $success === true) && $eulashown == false) {
                        $eulaaccepted = ($user->useragreementaccepted == 0) ?
                            $user->get_accepted_user_agreement() : $user->useragreementaccepted;
                        $userid = $linkarray["userid"];

                        // phpcs:disable Squiz.PHP.CommentedOutCode.Found,moodle.Commenting.InlineComment,moodle.Files.LineLength.TooLong
                        // Uncomment if ability to submit to Turnitin previously uploaded files will be implemented.
                        // if ($eulaaccepted != 1) {
                        //    $eulalink = html_writer::tag('span',
                        //        get_string('turnitinppulapost', 'plagiarism_turnitin'),
                        //        array('class' => 'pp_turnitin_eula_link tii_tooltip', 'id' => 'rubric_manager_form')
                        //    );
                        //    $eula = html_writer::tag('div', $eulalink, array('class' => 'pp_turnitin_eula', 'data-userid' => $user->id));
                        // }

                        // Show EULA launcher and form placeholder.
                        if (!empty($eula)) {
                            $output .= $eula;

                            $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
                            $turnitincall = $turnitincomms->initialise_api();

                            $customdata = ["disable_form_change_checker" => true,
                                    "elements" => [['html', $OUTPUT->box('', '', 'useragreement_inputs')]], ];
                            $eulaform = new \plagiarism_turnitin\turnitin_form(
                                $turnitincall->getApiBaseUrl() . TiiLTI::EULAENDPOINT,
                                $customdata,
                                'POST',
                                $target = 'eulaWindow',
                                ['id' => 'eula_launch']
                            );
                            $output .= $OUTPUT->box($eulaform->display(), 'tii_useragreement_form', 'useragreement_form');
                            $eulashown = true;
                        }
                    }
                }
            }

            // Resolve which users can view this submission's originality links.
            // For group submissions this expands to all group members.
            $submissionusers = \plagiarism_turnitin\turnitin_submission::resolve_submission_users(
                $cm,
                $moduledata,
                $linkarray["userid"],
                $context
            );

            // Proceed to displaying links for submissions.
            if ($istutor || in_array($USER->id, $submissionusers)) {
                // Prevent text content links being displayed for previous attempts as we have no way of getting the data.
                if (!empty($linkarray["content"]) && $linkarray["userid"] == $USER->id) {
                    $contentdisplayed = true;
                }

                // Get turnitin file details.
                if (is_null($plagiarismfile)) {
                    $params = [
                        'cm' => $linkarray["cmid"],
                        'identifier1' => $identifier,
                        'identifier2' => $oldidentifier,
                    ];
                    $sql = 'SELECT * FROM {plagiarism_turnitin_files}
                            WHERE cm = :cm
                            AND (identifier = :identifier1 OR identifier = :identifier2)
                            ORDER BY lastmodified DESC';
                    $plagiarismfiles = $DB->get_records_sql($sql, $params, 0, 1);
                    $plagiarismfile = current($plagiarismfiles);
                }

                // Fetch grade item for this CM.
                $gradeitem = $DB->get_record(
                    'grade_items',
                    ['iteminstance' => $cm->instance, 'itemmodule' => $cm->modname,
                     'courseid' => $cm->course, 'itemnumber' => 0]
                ) ?: null;

                $gradesreleased = \plagiarism_turnitin\turnitin_submission::resolve_grades_released(
                    $cm,
                    $moduledata,
                    $linkarray['userid'],
                    $gradeitem
                );

                $currentgradequery = $gradeitem
                    ? $moduleobject->get_current_gradequery($linkarray["userid"], $cm->instance, $gradeitem->id)
                    : false;

                $gradeexists = isset($currentgradequery->grade) && $currentgradequery->grade >= 0;
                $blindon     = !empty($moduledata->blindmarking) && empty($moduledata->revealidentities);

                $submittereulaccepted = \plagiarism_turnitin\turnitin_submission::resolve_submitter_eula_accepted(
                    $plagiarismfile !== null,
                    (int)$linkarray['userid'],
                    (int)$USER->id,
                    (int)$submittinguser,
                    isset($author) ? (int)$author : null,
                    $istutor,
                    $context,
                    $moduleobject
                );

                $ctx = \plagiarism_turnitin\turnitin_submission::build_submission_link_context(
                    $linkarray,
                    $cm,
                    $config,
                    $plagiarismsettings,
                    $plagiarismfile ?: null,
                    $submissiontype,
                    $submissionusers,
                    $istutor,
                    $isnonsubmitterforgroupassign,
                    $gradesreleased,
                    $gradeexists,
                    $blindon,
                    $gradeitem,
                    $_SESSION["peermark_assignments"][$cm->id] ?? [],
                    $submittereulaccepted,
                    $CFG->wwwroot,
                    !empty($linkarray["file"]) ? $file->get_filesize() : 0
                );
                $ctx->vieweruserid = $USER->id;

                $output .= \plagiarism_turnitin\turnitin_submission_display::render($ctx);
                $output .= html_writer::tag('div', '', ['class' => 'clear']);
            }

            if ($cm->modname == 'forum') {
                $output .= \plagiarism_turnitin\turnitin_eula_form::render($cm, $this);
            }

            $output = html_writer::tag('div', $output, ['class' => 'tii_links_container']);
        }

        // This comment is here as it is useful for product support.
        $plagiarismsettings = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);
        $turnitinassignid = (empty($plagiarismsettings['turnitin_assignid'])) ? '' : $plagiarismsettings['turnitin_assignid'];
        $output .= html_writer::tag(
            'span',
            '<!-- Turnitin Plagiarism plugin Version: ' . get_config('plagiarism_turnitin', 'version') .
            ' Course ID: ' . $coursedata->turnitin_cid . ' TII assignment ID: ' . $turnitinassignid . ' -->'
        );

        // If we're displaying links for an assignment with group submissions enabled, only show the DV link to the submitting student
        if ($isnonsubmitterforgroupassign) {
            $output .= html_writer::tag('div', get_string('nonsubmittingstudentinfo', 'plagiarism_turnitin'), ['class' => 'tii_nonsubmitter_info']);
        }

        return $output;
    }

    /**
     * Query Turnitin for the papers that need updated locally.
     *
     * @param object $cm The course module.
     * @return false
     */
    public function fetch_updated_paper_ids_from_turnitin($cm) {
        $plagiarismvalues = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);

        // Initialise Comms Object.
        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        // Get the submission ids from Turnitin that have been updated.
        try {
            $submission = new TiiSubmission();
            $submission->setAssignmentId($plagiarismvalues["turnitin_assignid"]);

            // Only update submissions that have been modified since last update.
            if (!empty($plagiarismvalues["grades_last_synced"])) {
                $submission->setDateFrom(gmdate("Y-m-d\TH:i:s\Z", $plagiarismvalues["grades_last_synced"]));
            }

            $response = $turnitincall->findSubmissions($submission);
            $findsubmission = $response->getSubmission();

            return $findsubmission->getSubmissionIds();
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
            return false;
        }
    }

    /**
     * Update grades from Turnitin.
     *
     * @param object $cm The course module.
     * @return bool|int
     */
    public function update_grades_from_tii($cm) {
        global $DB;

        $submissionids = $this->fetch_updated_paper_ids_from_turnitin($cm);
        if ($submissionids === false || count($submissionids) < 1) {
            return false;
        }
        // Refresh updated submissions.
        $return = true;
        // Initialise Comms Object.
        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        // Process submissions in batches, depending on the max. number of submissions the Turnitin API returns.
        $submissionbatches = array_chunk($submissionids, PLAGIARISM_TURNITIN_NUM_RECORDS_RETURN);

        foreach ($submissionbatches as $submissionsbatch) {
            try {
                $submission = new TiiSubmission();
                $submission->setSubmissionIds($submissionsbatch);

                $response = $turnitincall->readSubmissions($submission);
                $readsubmissions = $response->getSubmissions();

                foreach ($readsubmissions as $readsubmission) {
                    $submissiondata = $DB->get_record(
                        'plagiarism_turnitin_files',
                        ['externalid' => $readsubmission->getSubmissionId()],
                        'id'
                    );
                    $return = \plagiarism_turnitin\turnitin_submission::update(
                        $cm,
                        $submissiondata->id,
                        $readsubmission,
                        fn($cm, $tiisubmission, $userid) =>
                            \plagiarism_turnitin\turnitin_submission::update_gradebook(
                                $cm,
                                $submissiondata->id,
                                $tiisubmission,
                                $userid,
                                null,
                                $this
                            )
                    );
                }
            } catch (Exception $e) {
                $turnitincomms->handle_exceptions($e, 'tiisubmissiongeterror', false);
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Update grade from Turnitin.
     *
     * @param object $cm The course module.
     * @param int $submissionid The submission id.
     * @return bool
     */
    public function update_grade_from_tii($cm, $submissionid) {
        global $DB;
        $return = true;

        // Initialise Comms Object.
        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $submission = new TiiSubmission();
            $submission->setSubmissionId($submissionid);

            $response = $turnitincall->readSubmission($submission);

            $readsubmission = $response->getSubmission();

            $submissiondata = $DB->get_record(
                'plagiarism_turnitin_files',
                ['externalid' => $readsubmission->getSubmissionId()],
                'id'
            );

            \plagiarism_turnitin\turnitin_submission::update(
                $cm,
                $submissiondata->id,
                $readsubmission,
                fn($cm, $tiisubmission, $userid) =>
                    \plagiarism_turnitin\turnitin_submission::update_gradebook(
                        $cm,
                        $submissiondata->id,
                        $tiisubmission,
                        $userid,
                        null,
                        $this
                    )
            );
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
            $return = false;
        }

        return $return;
    }

    /**
     * Update module grade and gradebook.
     *
     * @param object $cm The course module.
     * @param object $submission The submission object.
     * @param int $userid The user id.
     * @param bool $cron Whether this is a cron job.
     */
    public function update_grade($cm, $submission, $userid, $cron = false) {
        global $DB, $USER, $CFG;
        $return = true;

        if (!is_null($submission->getGrade()) && $cm->modname != 'forum') {
            // Module grade object.
            $grade = new stdClass();
            // If submission has multiple content/files in it then get average grade.
            // Ignore NULL grades and files no longer part of submission.

            // Create module object.
            $moduleclass = "plagiarism_turnitin\\modules\\turnitin_" . $cm->modname;
            $moduleobject = new $moduleclass();

            // Get file from pathname hash.
            $submissiondata = $DB->get_record(
                'plagiarism_turnitin_files',
                ['externalid' => $submission->getSubmissionId()],
                'identifier'
            );

            // Get file as we need item id for discounting files that are no longer in submission.
            $fs = get_file_storage();
            if ($file = $fs->get_file_by_hash($submissiondata->identifier)) {
                $moodlefiles = $DB->get_records_select(
                    'files',
                    " component = ? AND itemid = ? AND source IS NOT null ",
                    [$moduleobject->filecomponent, $file->get_itemid()],
                    'id DESC',
                    'pathnamehash'
                );

                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($moodlefiles), SQL_PARAMS_QM, 'param', true);
                $tiisubmissions = $DB->get_records_select('plagiarism_turnitin_files', " userid = ? AND cm = ?
                AND identifier " . $insql, array_merge([$userid, $cm->id], $inparams));
            } else {
                $tiisubmissions = $DB->get_records('plagiarism_turnitin_files', ['userid' => $userid, 'cm' => $cm->id]);
                $tiisubmissions = current($tiisubmissions);
            }

            if (is_array($tiisubmissions) && count($tiisubmissions) > 1) {
                $averagegrade = null;
                $gradescounted = 0;
                foreach ($tiisubmissions as $tiisubmission) {
                    if (!is_null($tiisubmission->grade)) {
                        $averagegrade = $averagegrade + $tiisubmission->grade;
                        $gradescounted += 1;
                    }
                }
                $grade->grade = (!is_null($averagegrade) && $gradescounted > 0) ?
                    (int)round(($averagegrade / $gradescounted)) : null;
            } else {
                $grade->grade = $submission->getGrade();
            }

            // Check whether submission is a group submission - only applicable to assignment module.
            // If it's a group submission we will update the grade for everyone in the group.
            // Note: This will not work if the submitting user is in multiple groups.
            $userids = [$userid];
            $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);
            if ($cm->modname == "assign" && !empty($moduledata->teamsubmission)) {
                require_once($CFG->dirroot . '/mod/assign/locallib.php');
                $context = context_course::instance($cm->course);
                $assignment = new assign($context, $cm, null);

                if ($group = $assignment->get_submission_group($userid)) {
                    $users = groups_get_members($group->id);
                    $userids = array_keys($users);
                }
            }

            // Loop through all users and update grade.
            foreach ($userids as $userid) {
                // Get gradebook data.
                switch ($cm->modname) {
                    case 'assign':
                        // Query grades based on attempt number.
                        $gradesquery = ['userid' => $userid, 'assignment' => $cm->instance];

                        $usersubmissions = $DB->get_records(
                            'assign_submission',
                            $gradesquery,
                            'attemptnumber DESC',
                            'attemptnumber',
                            0,
                            1
                        );
                        $usersubmission = current($usersubmissions);
                        $attemptnumber = ($usersubmission) ? $usersubmission->attemptnumber : 0;
                        $gradesquery['attemptnumber'] = $attemptnumber;

                        $currentgrades = $DB->get_records('assign_grades', $gradesquery, 'id DESC');
                        $currentgrade = current($currentgrades);
                        break;
                    case 'workshop':
                        if (
                            $gradeitem = $DB->get_record('grade_items', ['iteminstance' => $cm->instance,
                                                        'itemmodule' => $cm->modname, 'itemnumber' => 0, ])
                        ) {
                            $currentgrade = $DB->get_record('grade_grades', ['userid' => $userid, 'itemid' => $gradeitem->id]);
                        }
                        break;
                }

                // Configure grade object and save to db.
                $table = $moduleobject->gradestable;
                $grade->timemodified = time();

                if ($currentgrade) {
                    $grade->id = $currentgrade->id;

                    if ($cm->modname == 'assign') {
                        $context = context_course::instance($cm->course);
                        if (has_capability('mod/assign:grade', $context, $USER->id)) {
                            // If the grade has changed and the change is not from a cron task then update the grader.
                            if ($currentgrade->grade != $grade->grade && $cron == false) {
                                $grade->grader = $USER->id;
                            }
                        }
                    }

                    $return = $DB->update_record($table, $grade);
                } else {
                    $grade->userid = $userid;
                    $grade->timecreated = time();
                    switch ($cm->modname) {
                        case 'workshop':
                            $grade->itemid = $gradeitem->id;
                            $grade->usermodified = $USER->id;
                            break;

                        case 'assign':
                            $grade->assignment = $cm->instance;
                            $grade->grader = $USER->id;
                            $grade->attemptnumber = $attemptnumber;
                            break;
                    }

                    $return = $DB->insert_record($table, $grade);
                }

                // Gradebook object.
                if ($grade) {
                    $grades = new stdClass();
                    $grades->userid = $userid;
                    $grades->rawgrade = $grade->grade;

                    // Check marking workflow state for assignments and only update gradebook if released.
                    if ($cm->modname == 'assign' && !empty($moduledata->markingworkflow)) {
                        $gradesreleased = $DB->record_exists(
                            'assign_user_flags',
                            [
                                                                    'userid' => $userid,
                                                                    'assignment' => $cm->instance,
                                                                    'workflowstate' => 'released',
                            ]
                        );
                        // Remove any existing grade from gradebook if not released.
                        if (!$gradesreleased) {
                            $grades->rawgrade = null;
                        }
                    }

                    // Prevent grades being passed to gradebook before identities have been revealed when blind marking is on.
                    if ($cm->modname == 'assign' && !empty($moduledata->blindmarking) && empty($moduledata->revealidentities)) {
                        return false;
                    }

                    // Update gradebook - Grade update returns 1 on failure and 0 if successful.
                    $gradeupdate = $cm->modname . "_grade_item_update";
                    require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
                    if (is_callable($gradeupdate)) {
                        $moduledata->cmidnumber = $cm->id;
                        $return = ($gradeupdate($moduledata, $grades)) ? false : true;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Create a course within Turnitin
     *
     * @param int $cmid The course module id.
     * @param string $modname The module name.
     * @param object $coursedata The course data.
     * @param string $workflowcontext The context of the workflow.
     */
    public function create_tii_course($cmid, $modname, $coursedata, $workflowcontext = "site") {
        global $CFG;

        // Create module object.
        $moduleclass = "plagiarism_turnitin\\modules\\turnitin_" . $modname;
        $moduleobject = new $moduleclass();

        $turnitinassignment = new \plagiarism_turnitin\turnitin_assignment(0);
        $turnitincourse = $turnitinassignment->create_tii_course($coursedata, $workflowcontext);

        // Join all admins and instructors to the course in Turnitin if it was created.
        if (!empty($turnitincourse->turnitin_cid)) {
            $admins = explode(",", $CFG->siteadmins);

            // Grab all instructors and extract the ids.
            $capability = $moduleobject->get_tutor_capability();
            if (!empty($cmid)) {
                $tutors = get_enrolled_users(context_module::instance($cmid), $capability, 0, 'u.id', 'u.id');
            } else {
                $tutors = get_enrolled_users(context_course::instance($coursedata->id), $capability, 0, 'u.id', 'u.id');
            }
            $tutorids = array_column((array)$tutors, 'id');

            $allinstructors = array_merge($admins, $tutorids);
            foreach ($allinstructors as $instructor) {
                // Create the admin as a user within Turnitin.
                $user = new \plagiarism_turnitin\turnitin_user($instructor, 'Instructor');
                $user->join_user_to_class($turnitincourse->turnitin_cid);
            }
        }

        return $turnitincourse;
    }

    /**
     * Get Peermark Assignments for this module from Turnitin.
     *
     * @param object $cm The course module.
     * @param string $tiiassignmentid The Turnitin assignment id.
     */
    public function refresh_peermark_assignments($cm, $tiiassignmentid) {
        global $DB;

        // Return here if the plugin is not configured for Turnitin.
        if (!\plagiarism_turnitin\turnitin_settings::is_plugin_configured()) {
            return;
        }

        // Initialise Comms Object.
        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $assignment = new TiiAssignment();
        $assignment->setAssignmentId($tiiassignmentid);

        try {
            $response = $turnitincall->readAssignment($assignment);
            $readassignment = $response->getAssignment();

            // Get Peermark Assignments.
            $peermarkassignments = $readassignment->getPeermarkAssignments();
            if ($peermarkassignments) {
                foreach ($peermarkassignments as $peermarkassignment) {
                    $peermark = new stdClass();
                    $peermark->tiiassignid = $peermarkassignment->getAssignmentId();
                    $peermark->parent_tii_assign_id = $readassignment->getAssignmentId();
                    $peermark->dtstart = strtotime($peermarkassignment->getStartDate());
                    $peermark->dtdue = strtotime($peermarkassignment->getDueDate());
                    $peermark->dtpost = strtotime($peermarkassignment->getFeedbackReleaseDate());
                    $peermark->maxmarks = (int)$peermarkassignment->getMaxGrade();
                    $peermark->title = $peermarkassignment->getTitle();

                    $currentpeermark = $DB->get_record(
                        'plagiarism_turnitin_peermark',
                        ['tiiassignid' => $peermark->tiiassignid]
                    );

                    if ($currentpeermark) {
                        $peermark->id = $currentpeermark->id;
                        $DB->update_record('plagiarism_turnitin_peermark', $peermark);
                    } else {
                        $DB->insert_record('plagiarism_turnitin_peermark', $peermark);
                    }
                }
            }
        } catch (Exception $e) {
            // We will use the locally stored assignment data if we can't connect to Turnitin.
            $turnitincomms->handle_exceptions($e, 'tiiassignmentgeterror', false);
        }
    }

    /**
     * Create the module as an assignment within Turnitin if it does not exist,
     * if we have a Turnitin id for the module then edit it
     *
     * @param object $cm The course module.
     * @param string $coursetiiid The Turnitin course id.
     * @param string $workflowcontext The context of the workflow.
     * @param bool $submittoturnitin Whether to submit to Turnitin.
     */
    public function sync_tii_assignment($cm, $coursetiiid, $workflowcontext = "site", $submittoturnitin = false) {
        global $DB;

        $config = \plagiarism_turnitin\turnitin_settings::admin_config();
        $modulepluginsettings = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);
        $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);

        // Configure assignment object to send to Turnitin.
        $assignment = new TiiAssignment();
        $assignment->setClassId($coursetiiid);

        // We need to truncate the moodle assignment title to be compatible with a Turnitin
        // assignment title (max length 99) and account for non English multibyte strings.
        $title = $moduledata->name;
        if (mb_strlen($moduledata->name, 'UTF-8') > 80) {
            $title = mb_substr($moduledata->name, 0, 80, 'UTF-8') . "...";
        }
        $assignment->setTitle($title);

        // Configure repository setting.
        $reposetting = (isset($modulepluginsettings["plagiarism_submitpapersto"])) ?
            $modulepluginsettings["plagiarism_submitpapersto"] : 1;

        // Override if necessary when admin is forcing standard/no repository.
        $reposetting = plagiarism_turnitin_override_repository($reposetting);

        $assignment->setSubmitPapersTo($reposetting);
        $assignment->setSubmittedDocumentsCheck($modulepluginsettings["plagiarism_compare_student_papers"]);
        $assignment->setInternetCheck($modulepluginsettings["plagiarism_compare_internet"]);
        $assignment->setPublicationsCheck($modulepluginsettings["plagiarism_compare_journals"]);
        if (
            $config->plagiarism_turnitin_repositoryoption == PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_EXPANDED ||
            $config->plagiarism_turnitin_repositoryoption == PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_INSTITUTIONAL
        ) {
            $institutioncheck = (isset($modulepluginsettings["plagiarism_compare_institution"])) ?
                $modulepluginsettings["plagiarism_compare_institution"] : 0;
            $assignment->setInstitutionCheck($institutioncheck);
        }

        $assignment->setAuthorOriginalityAccess($modulepluginsettings["plagiarism_show_student_report"]);
        $assignment->setResubmissionRule((int)$modulepluginsettings["plagiarism_report_gen"]);
        $assignment->setBibliographyExcluded($modulepluginsettings["plagiarism_exclude_biblio"]);
        $assignment->setQuotedExcluded($modulepluginsettings["plagiarism_exclude_quoted"]);
        $assignment->setSmallMatchExclusionType($modulepluginsettings["plagiarism_exclude_matches"]);

        if (empty($modulepluginsettings["plagiarism_exclude_matches_value"])) {
            $modulepluginsettings["plagiarism_exclude_matches_value"] = 0;
        }
        $assignment->setSmallMatchExclusionThreshold($modulepluginsettings["plagiarism_exclude_matches_value"]);

        // Don't set anonymous marking if there have been submissions.
        $previoussubmissions = $DB->record_exists(
            'plagiarism_turnitin_files',
            ['cm' => $cm->id, 'statuscode' => 'success']
        );

        // Use Moodle's blind marking setting for anonymous marking.
        if (isset($config->plagiarism_turnitin_useanon) && $config->plagiarism_turnitin_useanon && !$previoussubmissions) {
            $anonmarking = (!empty($moduledata->blindmarking)) ? 1 : 0;
            $assignment->setAnonymousMarking($anonmarking);
        }

        $assignment->setAllowNonOrSubmissions(!empty($modulepluginsettings["plagiarism_allow_non_or_submissions"]) ? 1 : 0);
        $assignment->setTranslatedMatching(!empty($modulepluginsettings["plagiarism_transmatch"]) ? 1 : 0);

        // Moodle handles submissions and whether they are allowed so this should always be true.
        // Otherwise, the Turnitin setting is incompatible with Moodle due to multiple files and resubmission rules.
        $assignment->setLateSubmissionsAllowed(1);
        $assignment->setMaxGrade(0);
        $assignment->setRubricId((!empty($modulepluginsettings["plagiarism_rubric"])) ?
            $modulepluginsettings["plagiarism_rubric"] : '');

        if (!empty($moduledata->grade)) {
            $assignment->setMaxGrade(($moduledata->grade < 0) ? 100 : (int)$moduledata->grade);
        }

        $dtstart = \plagiarism_turnitin\turnitin_date_utils::start_date($moduledata, $cm);
        $assignment->setStartDate(gmdate("Y-m-d\TH:i:s\Z", $dtstart));

        $gradeitem = $DB->get_record(
            'grade_items',
            ['iteminstance' => $cm->instance, 'itemmodule' => $cm->modname,
             'courseid' => $cm->course, 'itemnumber' => 0]
        ) ?: null;

        $gradesreleased = ($cm->modname === 'assign' && !empty($moduledata->markingworkflow))
            ? $DB->record_exists(
                'assign_user_flags',
                ['assignment' => $cm->instance, 'workflowstate' => 'released']
            )
            : false;

        $dtpost = \plagiarism_turnitin\turnitin_date_utils::post_date(
            $cm,
            $moduledata,
            $dtstart,
            $gradeitem,
            $gradesreleased
        );

        $dtdue = \plagiarism_turnitin\turnitin_date_utils::due_date($moduledata, $dtstart, $submittoturnitin);

        $assignment->setDueDate(gmdate("Y-m-d\TH:i:s\Z", $dtdue));

        // If the duedate is in the future then set any submission duedate_report_refresh flags that
        // are 2 to 1 to make sure they are re-examined in the next cron run.
        if ($dtdue > time()) {
            $DB->set_field(
                'plagiarism_turnitin_files',
                'duedate_report_refresh',
                1,
                ['cm' => $cm->id, 'duedate_report_refresh' => 2]
            );
        }

        $assignment->setFeedbackReleaseDate(gmdate("Y-m-d\TH:i:s\Z", $dtpost));

        // If we have a turnitin id then edit the assignment otherwise create it.
        if (
            $tiiassignment = $DB->get_record(
                'plagiarism_turnitin_config',
                ['cm' => $cm->id, 'name' => 'turnitin_assignid'],
                'value'
            )
        ) {
            $assignment->setAssignmentId($tiiassignment->value);
            $turnitinassignment = new \plagiarism_turnitin\turnitin_assignment(0);

            $return = $turnitinassignment->edit_tii_assignment($assignment, $workflowcontext);
            $return['errorcode'] = ($return['success']) ? 0 : 6;

            return $return;
        } else {
            $turnitinassignment = new \plagiarism_turnitin\turnitin_assignment(0);
            $turnitinassignid = $turnitinassignment->create_tii_assignment($assignment, $workflowcontext);

            if (!$turnitinassignid) {
                $return = ['success' => false, 'tiiassignmentid' => '', 'errorcode' => 5];
            } else {
                $moduleconfigvalue = new stdClass();
                $moduleconfigvalue->cm = $cm->id;
                $moduleconfigvalue->name = 'turnitin_assignid';
                $moduleconfigvalue->value = $turnitinassignid;
                $moduleconfigvalue->config_hash = $moduleconfigvalue->cm . "_" . $moduleconfigvalue->name;
                $DB->insert_record('plagiarism_turnitin_config', $moduleconfigvalue);

                $return = ['success' => true, 'tiiassignmentid' => $turnitinassignid];
            }

            return $return;
        }
    }

    /**
     * Check for rubric and save to assignment.
     *
     * @param object $cm The course module.
     */
    public function update_rubric_from_tii($cm) {
        global $DB;

        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();
        $assignment = new TiiAssignment();

        if (
            $tiimoduledata = $DB->get_record(
                'plagiarism_turnitin_config',
                ['cm' => $cm->id, 'name' => 'turnitin_assignid'],
                'value'
            )
        ) {
            $assignment->setAssignmentId($tiimoduledata->value);

            try {
                // Retrieve assignment from Turnitin.
                $response = $turnitincall->readAssignment($assignment);
                $tiiassignment = $response->getAssignment();

                // Create rubric config field with rubric value from Turnitin.
                $rubricfield = new stdClass();
                $rubricfield->cm = $cm->id;
                $rubricfield->name = 'plagiarism_rubric';
                $rubricfield->value = $tiiassignment->getRubricId();

                // Check if rubric already exists for this module.
                if (
                    $configfield = $DB->get_field(
                        'plagiarism_turnitin_config',
                        'id',
                        (['cm' => $cm->id, 'name' => 'plagiarism_rubric'])
                    )
                ) {
                    // Use current configfield to update rubric value.
                    $rubricfield->id = $configfield;

                    $DB->update_record('plagiarism_turnitin_config', $rubricfield);
                } else {
                    // Otherwise create rubric entry for this module.
                    $rubricfield->config_hash = $rubricfield->cm . "_" . $rubricfield->name;
                    $DB->insert_record('plagiarism_turnitin_config', $rubricfield);
                }
            } catch (Exception $e) {
                $turnitincomms->handle_exceptions($e, 'tiiassignmentgeterror', false);
            }
        }
    }

    /**
     * Updates the database field duedate_report_refresh for any given submission ID.
     * @param int $id - the ID of the submission to update.
     * @param int $newvalue - the value to which the field should be set.
     */
    public function set_duedate_report_refresh($id, $newvalue) {
        global $DB;

        $updatedata = new stdClass();
        $updatedata->id = $id;
        $updatedata->duedate_report_refresh = $newvalue;
        $DB->update_record('plagiarism_turnitin_files', $updatedata);
    }

    /**
     * Update simliarity scores.
     *
     * @return boolean
     */
    public function cron_update_scores() {
        global $DB;

        $submissionids = [];
        $reportsexpected = [];
        $assignmentids = [];

        // Grab all plagiarism files where all the following conditions are met:
        // 1. The file has been successfully sent to TII
        // 2. The submission is ready to recieve a similarity score (either it doesn't already have a similarity score or it's set to regenerate)
        // 3. The course or activity module associated with the submission hasn't been deleted
        $submissions = $DB->get_records_sql(
            'SELECT PTF.*,
          CM.instance AS instance,
          M.name AS modname
          FROM {plagiarism_turnitin_files} PTF
          INNER JOIN {course_modules} CM ON CM.id = PTF.cm
          INNER JOIN {modules} M ON M.id = CM.module
          WHERE statuscode = ?
          AND ( similarityscore IS NULL OR duedate_report_refresh = 1 )
          AND ( orcapable = ? OR orcapable IS NULL )
          AND externalid IS NOT NULL
          ORDER BY externalid DESC',
            ['success', 1]
        );

        // Cache module settings
        $modulesettings = [];
        foreach ($submissions as $tiisubmission) {
            if (!array_key_exists($tiisubmission->cm, $modulesettings)) {
                $modulesettings[$tiisubmission->cm] = \plagiarism_turnitin\turnitin_settings::for_cm($tiisubmission->cm);
            }
        }

        // Cache module data
        $moduledata = [];
        foreach ($submissions as $tiisubmission) {
            if (!array_key_exists($tiisubmission->modname, $moduledata)) {
                $moduledata[$tiisubmission->modname] = $DB->get_record($tiisubmission->modname, ['id' => $tiisubmission->instance]);
            }
        }

        // Add submission ids to the request.
        foreach ($submissions as $tiisubmission) {
            // Updates the db field 'duedate_report_refresh' if the due date has passed within the last twenty four hours.
            $now = strtotime('now');
            $dtdue = (!empty($moduledata[$tiisubmission->modname]->duedate)) ? $moduledata[$tiisubmission->modname]->duedate : 0;
            if ($tiisubmission->duedate_report_refresh != 1 && $now >= $dtdue && $now < strtotime('+1 day', $dtdue)) {
                $this->set_duedate_report_refresh($tiisubmission->id, 1);
            }

            if (!isset($reportsexpected[$tiisubmission->cm])) {
                $reportsexpected[$tiisubmission->cm] = 1;

                if (!isset($modulesettings[$tiisubmission->cm]['plagiarism_compare_institution'])) {
                    $modulesettings[$tiisubmission->cm]['plagiarism_compare_institution'] = 0;
                }

                // Don't add the submission to the request if module settings mean we will not get a report back.
                if (
                    array_key_exists('plagiarism_compare_student_papers', $modulesettings[$tiisubmission->cm]) &&
                    $modulesettings[$tiisubmission->cm]['plagiarism_compare_student_papers'] == 0 &&
                    $modulesettings[$tiisubmission->cm]['plagiarism_compare_internet'] == 0 &&
                    $modulesettings[$tiisubmission->cm]['plagiarism_compare_journals'] == 0 &&
                    $modulesettings[$tiisubmission->cm]['plagiarism_compare_institution'] == 0
                ) {
                    $reportsexpected[$tiisubmission->cm] = 0;
                }
            }

            // Only add the submission to the request if we are expecting an originality report.
            if ($reportsexpected[$tiisubmission->cm] == 1) {
                $submissionids[] = $tiisubmission->externalid;

                // If submission is added to the request, add the corresponding assign id in the assignids array.
                $moduleturnitinconfig = $DB->get_record(
                    'plagiarism_turnitin_config',
                    [ 'cm' => $tiisubmission->cm, 'name' => 'turnitin_assignid' ]
                );

                if (!isset(array_flip($assignmentids)[$moduleturnitinconfig->value])) {
                    $assignmentids[] = $moduleturnitinconfig->value;
                }
            }
        }

        $validatedsubmissions = $this->check_local_submission_state($assignmentids, $submissionids);

        // At this point update missingTiiSubmissions state to error.
        if (count($validatedsubmissions['missingTiiSubmissions']) > 0) {
            foreach ($validatedsubmissions['missingTiiSubmissions'] as $missingsubmission) {
                try {
                    \plagiarism_turnitin\turnitin_submission::invalidate_missing($missingsubmission);
                } catch (Exception $e) {
                    mtrace("An exception was thrown while attempting to update plagiarism turnitin file submission:
                    $missingsubmission "
                        . $e->getMessage() . '(' . $e->getFile() . ':' . $e->getLine() . ')');
                }
            }
        }

        if (count($validatedsubmissions['trimmedSubmissions']) > 0) {
            // Process submissions in batches, depending on the max. number of submissions the Turnitin API returns.
            $submissionbatches = array_chunk($validatedsubmissions['trimmedSubmissions'], PLAGIARISM_TURNITIN_NUM_RECORDS_RETURN);
            foreach ($submissionbatches as $submissionsbatch) {
                // Initialise Comms Object.
                $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
                $turnitincall = $turnitincomms->initialise_api();

                try {
                    $submission = new TiiSubmission();

                    // Use $submissionsbatch array instead of original $submissionids.
                    $submission->setSubmissionIds($submissionsbatch);
                    $response = $turnitincall->readSubmissions($submission);
                    $readsubmissions = $response->getSubmissions();

                    foreach ($readsubmissions as $readsubmission) {
                        // Catch exceptions thrown by getSubmissionId to allow rest of the
                        // submissions to get processed.
                        try {
                            $tiisubmissionid = (int)$readsubmission->getSubmissionId();

                            $currentsubmission = $DB->get_record(
                                'plagiarism_turnitin_files',
                                ['externalid' => $tiisubmissionid],
                                'id, cm, externalid, userid'
                            );
                            if ($cm = get_coursemodule_from_id('', $currentsubmission->cm)) {
                                $plagiarismfile = new stdClass();
                                $plagiarismfile->id = $currentsubmission->id;
                                $plagiarismfile->externalid = $tiisubmissionid;
                                $plagiarismfile->similarityscore = (is_numeric($readsubmission->getOverallSimilarity())) ?
                                    $readsubmission->getOverallSimilarity() : null;
                                $plagiarismfile->grade = (is_numeric($readsubmission->getGrade())) ? $readsubmission->getGrade()
                                    : null;
                                $plagiarismfile->orcapable = ($readsubmission->getOriginalityReportCapable() == 1) ? 1 : 0;
                                $plagiarismfile->transmatch = 0;
                                if (
                                    is_int($readsubmission->getTranslatedOverallSimilarity()) &&
                                    $readsubmission->getTranslatedOverallSimilarity() > $readsubmission->getOverallSimilarity()
                                ) {
                                    $plagiarismfile->similarityscore = $readsubmission->getTranslatedOverallSimilarity();
                                    $plagiarismfile->transmatch = 1;
                                }

                                if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                                    mtrace("File failed to update: " . $plagiarismfile->id);
                                } else {
                                    mtrace("File updated: " . $plagiarismfile->id);
                                }

                                // At the moment TII doesn't support double marking so we won't synchronise grades from Grade Mark
                                // as it would destroy the workflow.
                                if (!is_null($plagiarismfile->grade) && $cm->modname != "coursework") {
                                    $this->update_grade($cm, $readsubmission, $currentsubmission->userid, true);
                                }
                            }
                        } catch (Exception $e) {
                            mtrace("An exception was thrown while attempting to read submission $tiisubmissionid: "
                                   . $e->getMessage() . '(' . $e->getFile() . ':' . $e->getLine() . ')');
                        }
                    }
                } catch (Exception $e) {
                    mtrace(get_string('tiisubmissionsgeterror', 'plagiarism_turnitin'));
                    $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
                    // Do not return false if a batch fails - another one might work.
                }
            }
        }

        // Sets the duedate_report_refresh flag for each processed submission to 2 to prevent them being processed again in the
        // next cron run.
        foreach ($submissions as $tiisubmission) {
            $this->set_duedate_report_refresh($tiisubmission->id, 2);
        }

        return true;
    }

    /**
     * Check the local submission state against Turnitin.
     *
     * @param array $assignmentids
     * @param array $submissionids
     * @return array
     */
    private function check_local_submission_state($assignmentids, $submissionids) {
        // Initialise Comms Object.
        $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();
        $tiisubmissionids = [];

        foreach ($assignmentids as $assignmentid) {
            $submission = new TiiSubmission();
            $submission->setAssignmentId($assignmentid);

            try {
                $response = $turnitincall->findSubmissions($submission);
                $tiisubmissionids = array_merge($tiisubmissionids, $response->getSubmission()->getSubmissionIds());
            } catch (Exception $e) {
                mtrace("An exception was thrown while attempting to find submissions for Turnitin assignment: $assignmentid. "
                    . $e->getMessage() . '(' . $e->getFile() . ':' . $e->getLine() . ')');
            }
        }

        return [
            'trimmedSubmissions' => array_intersect($submissionids, $tiisubmissionids),
            'missingTiiSubmissions' => array_diff($submissionids, $tiisubmissionids),
        ];
    }

    /**
     * Migrate course from previous version of plugin to this
     *
     * @param object $coursedata The course data.
     * @param int $turnitincid The Turnitin course id.
     * @param string $workflowcontext The context of the workflow.
     */
    public function migrate_previous_course($coursedata, $turnitincid, $workflowcontext = "site") {
        global $DB;

        $turnitincourse = new stdClass();
        $turnitincourse->courseid = $coursedata->id;
        $turnitincourse->turnitin_cid = $turnitincid;
        $turnitincourse->turnitin_ctl = $coursedata->fullname . " (Moodle PP)";

        if (empty($coursedata->tii_rel_id)) {
            $method = "insert_record";
        } else {
            $method = "update_record";
            $turnitincourse->id = $coursedata->tii_rel_id;
        }

        if (!$DB->$method('plagiarism_turnitin_courses', $turnitincourse)) {
            if ($workflowcontext != "cron") {
                plagiarism_turnitin_print_error('classupdateerror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
                exit();
            }
        }

        $turnitinassignment = new \plagiarism_turnitin\turnitin_assignment(0);
        $turnitinassignment->edit_tii_course($coursedata);

        $coursedata->turnitin_cid = $turnitincid;
        $coursedata->turnitin_ctl = $turnitincourse->turnitin_ctl;

        return $coursedata;
    }


    /**
     * Queue submissions to send to Turnitin
     *
     * @param object $cm The course module.
     * @param string $author The author of the submission.
     * @param int $submitter The submitter.
     * @param string $identifier The identifier.
     * @param string $submissiontype The submission type.
     * @param int $itemid The item id.
     * @param string $eventtype The event type.
     * @return bool
     */
    public function queue_submission_to_turnitin(
        $cm,
        $author,
        $submitter,
        $identifier,
        $submissiontype,
        $itemid = 0,
        $eventtype = null
    ) {
        global $CFG, $DB, $turnitinacceptedfiles;
        $errorcode = 0;
        $attempt = 0;
        $tiisubmissionid = null;

        // If the EULA hasn't been accepted, don't save submission and don't submit to Tii.
        if (!plagiarism_turnitin_is_eula_accepted($author)) {
            $coursedata = \plagiarism_turnitin\turnitin_course::get_course_data($cm->id, $cm->course, 'site', $this);
            $user = new \plagiarism_turnitin\turnitin_user($author, "Learner");
            $user->join_user_to_class($coursedata->turnitin_cid);
            $eulaaccepted = ($user->useragreementaccepted == 0) ? $user->get_accepted_user_agreement() : $user->useragreementaccepted;
            if ($eulaaccepted != 1) {
                return true;
            }
        }

        // If the submission is already in the queue in an error state, remove it
        $DB->delete_records('plagiarism_turnitin_files', [
                'cm' => $cm->id,
                'userid' => $author,
                'itemid' => $itemid,
                'statuscode' => 'error',
            ]);

        // Check if file has been submitted before.
        $plagiarismfiles = plagiarism_turnitin_retrieve_successful_submissions($author, $cm->id, $identifier);
        if (count($plagiarismfiles) > 0) {
            return true;
        }

        $settings = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);

        if (!\plagiarism_turnitin\turnitin_settings::has_comparison_options($settings)) {
            // All comparison sources are disabled — no point sending to Turnitin.
            \plagiarism_turnitin\turnitin_logger::log(
                'No comparison options selected for assignment with cmid: ' . $cm->id . ' not sending to Turnitin',
                'NO_COMPARISON_OPTIONS_SELECTED'
            );
            return true;
        }
        // Get module data.
        $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);
        $moduledata->resubmission_allowed = false;

        if ($cm->modname == 'assign') {
            // Group submissions require userid = 0 when checking assign_submission.
            $userid = ($moduledata->teamsubmission) ? 0 : $author;

            if (!isset($_SESSION["moodlesubmissionstatus"])) {
                $_SESSION["moodlesubmissionstatus"] = null;
            }

            if ($eventtype == "content_uploaded" || $eventtype == "file_uploaded") {
                $moodlesubmission = $DB->get_record(
                    'assign_submission',
                    ['assignment' => $cm->instance,
                        'userid' => $userid,
                    'id' => $itemid,
                    ],
                    'status'
                );

                $_SESSION["moodlesubmissionstatus"] = $moodlesubmission->status;
            }

            $turnitinassign = new \plagiarism_turnitin\modules\turnitin_assign();
            $moduledata->resubmission_allowed = $turnitinassign->is_resubmission_allowed(
                $cm->instance,
                $settings["plagiarism_report_gen"],
                $submissiontype,
                $moduledata->maxattempts,
                $_SESSION["moodlesubmissionstatus"]
            );

            if ($eventtype != "content_uploaded" && $eventtype != "file_uploaded") {
                unset($_SESSION["moodlesubmissionstatus"]);
            }
        } else {
            $userid = $author;
        }

        // Work out submission id, Turnitin external id, and attempt via the routing logic.
        $timemodified = 0;
        if ($submissiontype == 'file') {
            $fs = get_file_storage();
            $file = $fs->get_file_by_hash($identifier);
            $timemodified = $file ? $file->get_timemodified() : 0;
        } else {
            $timemodified = \plagiarism_turnitin\turnitin_submission::get_content_timemodified(
                $cm,
                $submissiontype,
                $userid,
                $itemid
            );
        }

        $routing = \plagiarism_turnitin\turnitin_submission::resolve_submission_id(
            $cm,
            $author,
            $submissiontype,
            $identifier,
            $settings,
            $moduledata,
            $timemodified
        );
        if ($routing['earlyreturn']) {
            return true;
        }
        $submissionid    = $routing['submissionid'];
        $tiisubmissionid = $routing['tiisubmissionid'];
        $attempt         = $routing['attempt'];

        // Validate file size and extension, producing an errorcode when checks fail.
        if ($submissiontype == 'file') {
            $acceptanyfiletype = !empty($settings["plagiarism_allow_non_or_submissions"]);
            $errorcode = \plagiarism_turnitin\turnitin_submission::get_file_errorcode(
                $file,
                $acceptanyfiletype,
                $turnitinacceptedfiles
            );
        }

        // Save submission as queued or errored if we have an errorcode.
        $statuscode = ($errorcode != 0) ? 'error' : 'queued';
        return \plagiarism_turnitin\turnitin_submission::save(
            $cm,
            $author,
            $submissionid,
            $identifier,
            $statuscode,
            $tiisubmissionid,
            $submitter,
            $itemid,
            $submissiontype,
            $attempt,
            $errorcode
        );
    }

    /**
     * Amalgamated handler for Moodle cron events.
     *
     * @param object $eventdata
     * @return bool result
     */
    public function event_handler($eventdata) {
        global $DB;

        $result = true;

        // Get the coursemodule, use a different method if in a quiz as we have the quiz id.
        $cm = \plagiarism_turnitin\turnitin_submission::resolve_cm_from_event($eventdata);

        // Remove the event if the course module no longer exists.
        if (!$cm) {
            return true;
        }
        $context = context_module::instance($cm->id);

        // Initialise module settings.
        $plagiarismsettings = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);
        $plagiarismsettings = \plagiarism_turnitin\turnitin_submission::ensure_draft_submit_default(
            $plagiarismsettings,
            $cm->modname
        );
        $moduletiienabled = \plagiarism_turnitin\turnitin_settings::module_enabled('mod_' . $cm->modname);

        // Either module not using Turnitin or Turnitin not being used at all so return true to remove event from queue.
        if (!\plagiarism_turnitin\turnitin_settings::should_process_event($plagiarismsettings, $moduletiienabled)) {
            return true;
        }

        // Get module data.
        $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);
        if ($cm->modname != 'assign') {
            $moduledata->submissiondrafts = 0;
        }

        // If draft submissions are turned on then only send to Turnitin if the draft submit setting is set.
        if (\plagiarism_turnitin\turnitin_settings::should_skip_draft($moduledata, $plagiarismsettings, $eventdata['eventtype'])) {
            return true;
        }

        // Set the author and submitter.
        $submitter = $eventdata['userid'];
        $author = \plagiarism_turnitin\turnitin_submission::resolve_author($eventdata, $cm);

        // Resolve the real author when an instructor submits on behalf of a group student.
        $author = \plagiarism_turnitin\turnitin_submission::resolve_instructor_group_author(
            $eventdata,
            $cm,
            $context,
            $submitter,
            $author
        );

        // Get actual text content and files for assessable_submitted events.
        // As this won't be present in eventdata for this event type.
        if ($eventdata['other']['modulename'] == 'assign' && $eventdata['eventtype'] == "assessable_submitted") {
            $eventdata = \plagiarism_turnitin\turnitin_submission::enrich_assessable_submitted($eventdata, $author);
        }

        // Remove submission from Turnitin queue if it is removed from Moodle.
        if ($eventdata['other']['modulename'] == 'assign' && $eventdata['eventtype'] == "submission_removed") {
            \plagiarism_turnitin\turnitin_submission::remove_queued_for_submission(
                $eventdata['contextinstanceid'],
                $eventdata['relateduserid'],
                $eventdata['objectid']
            );
        }

        // Queue every question submitted in a quiz attempt.
        if ($eventdata['eventtype'] == 'quiz_submitted') {
            $attempt = \mod_quiz\quiz_attempt::create($eventdata['objectid']);

            foreach ($attempt->get_slots() as $slot) {
                $qa = $attempt->get_question_attempt($slot);
                if ($qa->get_question()->get_type_name() != 'essay') {
                    continue;
                }
                $eventdata['other']['content'] = $qa->get_response_summary();

                // Queue text content only if the file submission type indicates that the quiz question contains online text
                if ($qa->get_question()->responseformat !== 'noinline') {
                    // We don't have access to the text content in the event handler, so use userid, cmid, slot, and attempt number to create a unique hash
                    $identifier = sha1('quiz_attempt user' . $attempt->get_userid() . ' cm' . $cm->id . ' slot' . $slot . ' attempt' . $attempt->get_attempt_number());
                    $result = $this->queue_submission_to_turnitin(
                        $cm,
                        $author,
                        $submitter,
                        $identifier,
                        'quiz_answer',
                        $eventdata['objectid'],
                        $eventdata['eventtype']
                    );
                }

                $files = $qa->get_last_qt_files('attachments', $context->id);
                foreach ($files as $file) {
                    // Queue file for sending to Turnitin.
                    $identifier = $file->get_pathnamehash();
                    $result = $this->queue_submission_to_turnitin(
                        $cm,
                        $author,
                        $submitter,
                        $identifier,
                        'file',
                        $eventdata['objectid'],
                        $eventdata['eventtype']
                    );
                }
            }
        }

        // Define the queue callable that wraps $this->queue_submission_to_turnitin.
        $queuefn = fn($cm, $author, $submitter, $identifier, $submissiontype, $objectid, $eventtype) =>
            $this->queue_submission_to_turnitin($cm, $author, $submitter, $identifier, $submissiontype, $objectid, $eventtype);

        // Queue text content and forum posts to send to Turnitin.
        $result = \plagiarism_turnitin\turnitin_submission::queue_text_content(
            $eventdata,
            $cm,
            $author,
            $submitter,
            $queuefn
        );

        // Queue files to submit to Turnitin.
        $result = $result && \plagiarism_turnitin\turnitin_submission::queue_file_submissions(
            $eventdata,
            $cm,
            $author,
            $submitter,
            $queuefn
        );

        return $result;
    }

    /**
     * Clean up previous file submissions.
     * Moodle will remove any old files or drafts during cron execution and file submission.
     *
     * @param object $cm The course module.
     * @param int $userid The user id.
     * @param int $itemid The item id.
     * @param string $submissiontype The submission type.
     * @param string $identifier The identifier.
     */
    public function clean_old_turnitin_submissions($cm, $userid, $itemid, $submissiontype, $identifier) {
        global $DB, $CFG;
        $deletestr = '';

        // Create module object.
        $moduleclass = "plagiarism_turnitin\\modules\\turnitin_" . $cm->modname;
        $moduleobject = new $moduleclass();

        if ($submissiontype == 'file') {
            // If this is an assignment then we need to account for previous attempts so get other items ids.
            if ($cm->modname == 'assign') {
                $itemids = $DB->get_records('assign_submission', [
                                                                    'assignment' => $cm->instance,
                                                                    'userid' => $userid,
                                                                    ], '', 'id');

                // Only proceed if we have item ids.
                if (empty($itemids)) {
                    return true;
                } else {
                    [$itemidsinsql, $itemidsparams] = $DB->get_in_or_equal(array_keys($itemids));
                    $itemidsinsql = ' itemid ' . $itemidsinsql;
                    $params = array_merge([$moduleobject->filecomponent, $userid], $itemidsparams);
                }
            } else {
                $itemidsinsql = ' itemid = ? ';
                $params = [$moduleobject->filecomponent, $userid, $itemid];
            }

            if (
                $moodlefiles = $DB->get_records_select('files', " component = ? AND userid = ? AND source IS NOT null AND " .
                $itemidsinsql, $params, 'id DESC', 'pathnamehash')
            ) {
                [$notinsql, $notinparams] = $DB->get_in_or_equal(array_keys($moodlefiles), SQL_PARAMS_QM, 'param', false);
                $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";
                $oldfiles = $DB->get_records_select(
                    'plagiarism_turnitin_files',
                    " userid = ? AND cm = ? " .
                                                                            " AND " . $typefield . " = ? AND identifier " . $notinsql,
                    array_merge([$userid, $cm->id, 'file'], $notinparams)
                );

                if (!empty($oldfiles)) {
                    foreach ($oldfiles as $oldfile) {
                        // Delete submission from Turnitin if we have an external id.
                        if (!is_null($oldfile->externalid)) {
                            \plagiarism_turnitin\turnitin_submission::delete($cm, $oldfile->externalid, $userid);
                        }
                        $deletestr .= $oldfile->id . ', ';
                    }

                    [$insql, $deleteparams] = $DB->get_in_or_equal(explode(',', substr($deletestr, 0, -2)));
                    $deletestr = " id " . $insql;
                }
            }
        } else if ($submissiontype == 'text_content') {
            $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";
            $deletestr = " userid = ? AND cm = ? AND " . $typefield . " = ? AND identifier != ? ";
            $deleteparams = [$userid, $cm->id, 'text_content', $identifier];
        }

        // Delete from database.
        if (!empty($deletestr)) {
            $DB->delete_records_select('plagiarism_turnitin_files', $deletestr, $deleteparams);
        }
    }

    /**
     * Get the parameters for report gen speed.
     *
     * @return object The parameters for report gen speed.
     */
    public function plagiarism_get_report_gen_speed_params() {
        $genparams = new stdClass();
        $genparams->num_resubmissions = PLAGIARISM_TURNITIN_REPORT_GEN_SPEED_NUM_RESUBMISSIONS;
        $genparams->num_hours = PLAGIARISM_TURNITIN_REPORT_GEN_SPEED_NUM_HOURS;

        return $genparams;
    }
}

/**
 * Add the Turnitin settings form to an add/edit activity page
 *
 * @param moodleform $formwrapper
 * @param MoodleQuickForm $mform
 * @return void
 */
function plagiarism_turnitin_coursemodule_standard_elements($formwrapper, $mform) {
    $context = context_course::instance($formwrapper->get_course()->id);
    $modulename = isset($formwrapper->get_current()->modulename) ? 'mod_' . $formwrapper->get_current()->modulename : '';

    \plagiarism_turnitin\turnitin_activitysettingsform::add_to_form($mform, $context, $modulename);
}

/**
 * Handle saving data from the Turnitin settings form..
 *
 * @param stdClass $data
 * @param stdClass $course
 */
function plagiarism_turnitin_coursemodule_edit_post_actions($data, $course) {
    \plagiarism_turnitin\turnitin_settings::save_for_cm($data);

    return $data;
}

/**
 * Send a single queued submission to Turnitin.
 *
 * Called by both the scheduled task (send_submissions) and the ad-hoc task
 * (adhoc_send_submission) to process one row from plagiarism_turnitin_files.
 *
 * @param plagiarism_plugin_turnitin $pluginturnitin Plugin instance
 * @param stdClass $queueditem Row from plagiarism_turnitin_files
 */
function plagiarism_turnitin_send_single_submission($pluginturnitin, $queueditem) {
    global $CFG, $DB, $turnitinacceptedfiles;

    $config = \plagiarism_turnitin\turnitin_settings::admin_config();

    // Don't attempt to call Turnitin if a connection to Turnitin could not be established.
    if (!$pluginturnitin->test_turnitin_connection()) {
        mtrace(get_string('ppeventsfailedconnection', 'plagiarism_turnitin'));
        return;
    }

    // Don't proceed if we can not find a cm.
    $cm = \plagiarism_turnitin\turnitin_submission::check_cm_exists($queueditem);
    if ($cm === false) {
        return;
    }

    // Get various settings that we need.
    $errorcode = 0;
    $settings = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);

    // Create module object.
    if (empty($cm->modname)) {
        \plagiarism_turnitin\turnitin_submission::save_errored($queueditem->id, $queueditem->attempt, 15);

        // Output a message in the cron for failed submission to Turnitin.
        $outputvars = new stdClass();
        $outputvars->id = $queueditem->id;
        $outputvars->cm = $queueditem->cm;
        $outputvars->userid = $queueditem->userid;

        \plagiarism_turnitin\turnitin_logger::log(get_string('errorcode15', 'plagiarism_turnitin', $outputvars), "PP_NO_ACTIVITY_MODULE");
        return;
    }

    $moduleclass = "plagiarism_turnitin\\modules\\turnitin_" . $cm->modname;
    $moduleobject = new $moduleclass();

    // Get module data.
    $moduledata = $DB->get_record($cm->modname, ['id' => $cm->instance]);
    $moduledata->resubmission_allowed = false;

    if ($cm->modname == 'assign') {
        // Group submissions require userid = 0 when checking assign_submission.
        $userid = ($moduledata->teamsubmission) ? 0 : $queueditem->userid;

        $moodlesubmission = $DB->get_record(
            'assign_submission',
            ['assignment' => $cm->instance,
             'userid'     => $userid,
            'id'         => $queueditem->itemid,
            ],
            'status'
        );

        $moduledata->resubmission_allowed = $moduleobject->is_resubmission_allowed(
            $cm->instance,
            $settings["plagiarism_report_gen"] ?? 0,
            $queueditem->submissiontype,
            $moduledata->maxattempts,
            $moodlesubmission->status ?? null
        );
    }

    // Get course data.
    $coursedata = \plagiarism_turnitin\turnitin_course::get_course_data($cm->id, $cm->course, 'cron', $pluginturnitin);
    // Save failed submission if class can not be created.
    if (empty($coursedata->turnitin_cid)) {
        \plagiarism_turnitin\turnitin_submission::save_errored($queueditem->id, $queueditem->attempt, 10);
        return;
    }

    // Update course data in Turnitin.
    $turnitinassignment = new \plagiarism_turnitin\turnitin_assignment(0);
    $turnitinassignment->edit_tii_course($coursedata);

    // Previously failed submissions may not have a value for submitter.
    if (empty($queueditem->submitter)) {
        $queueditem->submitter = $queueditem->userid;
    }

    // User Id should never be 0 but save as errored for old submissions where this may be the case.
    if (!\plagiarism_turnitin\turnitin_submission::check_userid_valid($queueditem)) {
        return;
    }

    // Join User to course.
    try {
        $user = new \plagiarism_turnitin\turnitin_user($queueditem->userid, 'Learner', true, 'cron');
        $user->edit_tii_user();
        $user->join_user_to_class($coursedata->turnitin_cid);
    } catch (Exception $e) {
        $user = new \plagiarism_turnitin\turnitin_user($queueditem->userid, 'Learner', 'false', 'cron', 'false');
        $errorcode = 7;
    }

    // Update assignment details in Turnitin.
    $syncassignment = $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron", true);

    // Any errorcode from assignment sync needs to be saved.
    if (!empty($syncassignment['errorcode'])) {
        $errorcode = $syncassignment['errorcode'];
    }

    // Don't submit if a user has not accepted the eula.
    if ($queueditem->userid == $queueditem->submitter && $user->useragreementaccepted != 1) {
        $errorcode = 3;
    }

    // There should never not be a submission type, handle if there isn't just in case.
    if (!\plagiarism_turnitin\turnitin_submission::check_submission_type_valid($queueditem->submissiontype)) {
        $errorcode = 11;
    }

    if (!empty($errorcode)) {
        // Save failed submission if user can not be joined to class or there was an error with the assignment.
        \plagiarism_turnitin\turnitin_submission::save_errored($queueditem->id, $queueditem->attempt, $errorcode);
        return;
    }

    // Clean up old Turnitin submission files.
    if ($queueditem->itemid != 0 && $queueditem->submissiontype == 'file' && $cm->modname != 'forum') {
        $pluginturnitin->clean_old_turnitin_submissions(
            $cm,
            $user->id,
            $queueditem->itemid,
            $queueditem->submissiontype,
            $queueditem->identifier
        );
    }

    // Get more Submission Details as required.
    $apimethod = "createSubmission";
    switch ($queueditem->submissiontype) {
        case 'file':
            $acceptanyfiletype = (!empty($settings["plagiarism_allow_non_or_submissions"])) ? true : false;
            $assigncontent = $moduleobject->get_submission_content(
                $queueditem,
                $cm,
                $moduledata,
                $acceptanyfiletype,
                $turnitinacceptedfiles
            );
            $apimethod   = $assigncontent['apimethod'];
            $textcontent = $assigncontent['textcontent'];
            $title       = $assigncontent['title'];
            $filename    = $assigncontent['filename'];
            $errorcode   = $assigncontent['errorcode'];

            if ($errorcode !== 0) {
                mtrace('File submission error for identifier: ' . $queueditem->identifier);
            }

            break;

        case 'text_content':
            if ($cm->modname === 'assign') {
                $assigncontent = $moduleobject->get_submission_content(
                    $queueditem,
                    $cm,
                    $moduledata,
                    false,
                    []
                );
                $apimethod   = $assigncontent['apimethod'];
                $textcontent = $assigncontent['textcontent'];
                $title       = $assigncontent['title'];
                $filename    = $assigncontent['filename'];
                $errorcode   = $assigncontent['errorcode'];
            } else if ($cm->modname === 'workshop') {
                // Workshop text content remains inline pending extraction into turnitin_workshop::get_submission_content().
                $moodlesubmission = $DB->get_record(
                    'workshop_submissions',
                    ['id' => $queueditem->itemid],
                    'content'
                );
                $textcontent = html_to_text($moodlesubmission->content);
                $title = 'onlinetext_' . $user->id . '_' . $cm->id . '_' . $cm->instance . '.txt';
                $filename = $title;

                if (!is_null($queueditem->externalid)) {
                    $apimethod = ($moduledata->resubmission_allowed) ? 'replaceSubmission' : 'createSubmission';
                }
            }

            if ($errorcode === 0) {
                // Delete old text content submissions from Turnitin if not replacing.
                if (!is_null($queueditem->externalid) && $settings["plagiarism_report_gen"] == 0) {
                    \plagiarism_turnitin\turnitin_submission::delete($cm, $queueditem->externalid, $queueditem->userid);
                }

                // Remove any old text submissions from Moodle DB — only one text submission per user is kept.
                if (!empty($queueditem->itemid)) {
                    $pluginturnitin->clean_old_turnitin_submissions(
                        $cm,
                        $user->id,
                        $queueditem->itemid,
                        $queueditem->submissiontype,
                        $queueditem->identifier
                    );
                }
            }

            break;

        case 'forum_post':
            $forumcontent = $moduleobject->get_submission_content($queueditem, $cm, $settings["plagiarism_report_gen"]);
            $apimethod    = $forumcontent['apimethod'];
            $textcontent  = $forumcontent['textcontent'];
            $title        = $forumcontent['title'];
            $filename     = $forumcontent['filename'];
            $errorcode    = $forumcontent['errorcode'];
            if ($errorcode !== 0) {
                mtrace('File content not found on submission. Identifier: ' . $queueditem->identifier);
            }
            break;

        case 'quiz_answer':
            $quizcontent = $moduleobject->get_submission_content(
                $queueditem,
                $cm,
                $user->id,
                $settings["plagiarism_report_gen"]
            );
            $apimethod   = $quizcontent['apimethod'];
            $textcontent = $quizcontent['textcontent'];
            $title       = $quizcontent['title'];
            $filename    = $quizcontent['filename'];
            $errorcode   = $quizcontent['errorcode'];
            if ($errorcode !== 0) {
                mtrace('Quiz answer content not found on submission. Identifier: ' . $queueditem->identifier);
            }
            break;
    }

    // Save failed submission and don't process any further.
    if ($errorcode != 0) {
        \plagiarism_turnitin\turnitin_submission::save_errored($queueditem->id, $queueditem->attempt, $errorcode);
        return;
    }

    // Read the stored file/content into a temp file for submitting.
    $filestring = \plagiarism_turnitin\turnitin_submission::build_submission_filestring(
        $title,
        $cm,
        $user,
        $moduledata,
        $config
    );

    // Don't proceed if we can not create a tempfile.
    try {
        $tempfile = plagiarism_turnitin_tempfile($filestring, $filename);
    } catch (Exception $e) {
        \plagiarism_turnitin\turnitin_submission::save_errored($queueditem->id, $queueditem->attempt, 8);
        return;
    }

    $fh = fopen($tempfile, "w");
    fwrite($fh, $textcontent);
    fclose($fh);

    // Create submission object.
    $submission = new TiiSubmission();
    $submission->setAssignmentId($syncassignment['tiiassignmentid']);
    if ($apimethod == "replaceSubmission") {
        $submission->setSubmissionId($queueditem->externalid);
    }
    $submission->setTitle($title);
    $submission->setAuthorUserId($user->tiiuserid);

    // Account for submission by teacher in assignment module.
    $submission->setSubmitterUserId($user->tiiuserid);
    $submission->setRole('Learner');

    if ($queueditem->userid != $queueditem->submitter) {
        $instructor = new \plagiarism_turnitin\turnitin_user($queueditem->submitter, 'Instructor');

        // These should be true but in case of an edge case where a user has been deleted in Tii.
        if ($instructor->edit_tii_user() && $instructor->join_user_to_class($coursedata->turnitin_cid)) {
            $submission->setSubmitterUserId($instructor->tiiuserid);
            $submission->setRole('Instructor');
        }
    }

    $submission->setSubmissionDataPath($tempfile);

    // Initialise Comms Object.
    $turnitincomms = new \plagiarism_turnitin\turnitin_comms();
    $turnitincall = $turnitincomms->initialise_api();

    try {
        $response = $turnitincall->$apimethod($submission);
        $newsubmission = $response->getSubmission();
        $tiisubmissionid = $newsubmission->getSubmissionId();

        \plagiarism_turnitin\turnitin_submission::save(
            $cm,
            $user->id,
            $queueditem->id,
            $queueditem->identifier,
            'success',
            $tiisubmissionid,
            $queueditem->submitter,
            $queueditem->itemid,
            $queueditem->submissiontype,
            $queueditem->attempt
        );

        // Delete the tempfile.
        if (!is_null($tempfile)) {
            unlink($tempfile);
        }

        plagiarism_turnitin_lock_anonymous_marking($cm->id);

        // Send a message to the user's Moodle inbox with the digital receipt.
        $receipt = new \plagiarism_turnitin\digitalreceipt\pp_receipt_message();
        $input = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'submission_title' => $title,
            'assignment_name' => $moduledata->name,
            'course_fullname' => $coursedata->turnitin_ctl,
            'submission_date' => date('d-M-Y h:iA'),
            'submission_id' => $tiisubmissionid,
        ];

        $message = $receipt->build_message($input);
        $receipt->send_message($user->id, $message, $cm->course);

        // Output a message in the cron for successfull submission to Turnitin.
        $outputvars = new stdClass();
        $outputvars->title = $title;
        $outputvars->submissionid = $tiisubmissionid;
        $outputvars->assignmentname = $moduledata->name;
        $outputvars->coursename = $coursedata->turnitin_ctl;

        mtrace(get_string('cronsubmittedsuccessfully', 'plagiarism_turnitin', $outputvars));
    } catch (Exception $e) {
        // Save that submission errored.
        $submissionerrormsg = get_string('pp_submission_error', 'plagiarism_turnitin') . ' ' . $e->getMessage();
        \plagiarism_turnitin\turnitin_submission::save(
            $cm,
            $user->id,
            $queueditem->id,
            $queueditem->identifier,
            'error',
            null,
            $queueditem->submitter,
            $queueditem->itemid,
            $queueditem->submissiontype,
            $queueditem->attempt,
            0,
            $submissionerrormsg
        );

        $errorstring = (empty($queueditem->externalid)) ? "pp_createsubmissionerror" : "pp_updatesubmissionerror";
        $turnitincomms->handle_exceptions($e, $errorstring, false);

        // Output error in the cron.
        mtrace('-------------------------');
        mtrace(get_string('pp_submission_error', 'plagiarism_turnitin') . ': ' . $e->getMessage());
        mtrace('User:  ' . $user->id . ' - ' . $user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')');
        mtrace('Course Module: ' . $cm->id . '');
        mtrace('-------------------------');
    }
}

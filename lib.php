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

/*
 * @package   turnitintooltwo
 * @copyright 2013 iParadigms LLC
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

define('PLAGIARISM_TURNITIN_NUM_RECORDS_RETURN', 500);

global $tiipp;
$tiipp = new stdClass();
$tiipp->in_use = true;

// Get global class.
require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/lib.php');
if ($CFG->branch < 28) {
    require_once($CFG->libdir.'/pluginlib.php');
}
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/turnitintooltwo_view.class.php');
require_once(__DIR__."/turnitinplugin_view.class.php");

class plagiarism_plugin_turnitin extends plagiarism_plugin {

    /**
     * Get the fields to be used in the form to configure each activities Turnitin settings.
     *
     * @return array of settings fields
     */
    public function get_settings_fields() {
        return array('use_turnitin', 'plagiarism_show_student_report', 'plagiarism_draft_submit',
                        'plagiarism_allow_non_or_submissions', 'plagiarism_submitpapersto', 'plagiarism_compare_student_papers',
                        'plagiarism_compare_internet', 'plagiarism_compare_journals', 'plagiarism_report_gen',
                        'plagiarism_compare_institution', 'plagiarism_exclude_biblio', 'plagiarism_exclude_quoted',
                        'plagiarism_exclude_matches', 'plagiarism_exclude_matches_value', 'plagiarism_rubric', 'plagiarism_erater',
                        'plagiarism_erater_handbook', 'plagiarism_erater_dictionary', 'plagiarism_erater_spelling',
                        'plagiarism_erater_grammar', 'plagiarism_erater_usage', 'plagiarism_erater_mechanics',
                        'plagiarism_erater_style', 'plagiarism_anonymity', 'plagiarism_transmatch');
    }

    /**
     * Get the configuration settings for the plagiarism plugin
     *
     * @return mixed if plugin is enabled then an array of config settings is returned or false if not
     */
    public static function get_config_settings($modulename) {
        global $DB;
        if ($turnitinsetting = $DB->get_record('config_plugins', array('name' => 'turnitin_use_'.$modulename, 'plugin' => 'plagiarism'))) {
            $configsettings['turnitin_use_'.$modulename] = $turnitinsetting->value;
        } else {
            $configsettings['turnitin_use_'.$modulename] = 0;
        }

        return $configsettings;
    }

    /**
     * Get the Turnitin settings for a module
     *
     * @param int $cm_id  the course module id, if this is 0 the default settings will be retrieved
     * @return array of Turnitin settings for a module
     */
    public function get_settings($cmid = 0) {
        global $DB;
        $defaults = $DB->get_records_menu('plagiarism_turnitin_config', array('cm' => 0),     '', 'name,value');
        $settings = $DB->get_records_menu('plagiarism_turnitin_config', array('cm' => $cmid), '', 'name,value');

        // Enforce site wide config locking.
        foreach ($defaults as $key => $value){
            if (substr($key,-5) !== '_lock'){
                continue;
            }
            if ($value != 1){
                continue;
            }
            $setting = substr($key,0,-5);
            $default = $defaults[$setting];
            $settings[$setting] = $default;
        }

        return $settings;
    }

    public function get_file_upload_errors() {
        global $DB;

        $files = $DB->get_records_sql("SELECT PTF.id, U.firstname, U.lastname, U.email, PTF.cm, M.name AS moduletype,
                                        C.id AS courseid, C.fullname AS coursename, PTF.identifier, PTF.submissiontype,
                                        PTF.errorcode, PTF.errormsg
                                        FROM {plagiarism_turnitin_files} PTF
                                        LEFT JOIN {user} U ON U.id = PTF.userid
                                        LEFT JOIN {course_modules} CM ON CM.id = PTF.cm
                                        LEFT JOIN {modules} M ON CM.module = M.id
                                        LEFT JOIN {course} C ON CM.course = C.id
                                        WHERE PTF.statuscode != 'success'
                                        ORDER BY PTF.id DESC");
        return $files;
    }

    /**
     * Save the form data associated with the plugin
     *
     * @global type $DB
     * @param object $data the form data to save
     */
    public function save_form_elements($data) {
        global $DB;

        $configsettings = $this->get_config_settings('mod_'.$data->modulename);
        if (empty($configsettings['turnitin_use_mod_'.$data->modulename])) {
            return;
        }

        $settingsfields = $this->get_settings_fields();
        // Get current values.
        $plagiarismvalues = $this->get_settings($data->coursemodule);

        foreach ($settingsfields as $field) {
            if (isset($data->$field)) {
                $optionfield = new object();
                $optionfield->cm = $data->coursemodule;
                $optionfield->name = $field;
                $optionfield->value = $data->$field;

                if (isset($plagiarismvalues[$field])) {
                    $optionfield->id = $DB->get_field('plagiarism_turnitin_config', 'id',
                                                 (array('cm' => $data->coursemodule, 'name' => $field)));
                    if (!$DB->update_record('plagiarism_turnitin_config', $optionfield)) {
                        turnitintooltwo_print_error('defaultupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    }
                } else {
                    if (!$DB->insert_record('plagiarism_turnitin_config', $optionfield)) {
                        turnitintooltwo_print_error('defaultinserterror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    }
                }
            }
        }
    }

    /**
     * Add the Turnitin settings form to an add/edit activity page
     *
     * @param object $mform
     * @param object $context
     * @return type
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        global $DB;

        if (has_capability('plagiarism/turnitin:enable', $context)) {
            // Get Course module id and values.
            $cmid = optional_param('update', 0, PARAM_INT);

            // Only 2.4+ passes in the modulename so in 2.3 when adding a module
            // we can not differentiate whether plugin is enabled by module.
            if (!empty($modulename)) {
                $configsettings = $this->get_config_settings($modulename);
                if (empty($configsettings['turnitin_use_'.$modulename])) {
                    return;
                }
            }

            $plagiarismvalues = $this->get_settings($cmid);
            $plagiarismelements = $this->get_settings_fields();

            $turnitinpluginview = new turnitinplugin_view();
            $plagiarismvalues["plagiarism_rubric"] = ( !empty($plagiarismvalues["plagiarism_rubric"]) ) ?
                                                                $plagiarismvalues["plagiarism_rubric"] : 0;
            $turnitinpluginview->add_elements_to_settings_form($mform, "activity", $cmid, $plagiarismvalues["plagiarism_rubric"]);

            // Disable all plagiarism elements if turnitin is not enabled.
            foreach ($plagiarismelements as $element) {
                if ($element <> 'use_turnitin') { // Ignore this var.
                    $mform->disabledIf($element, 'use_turnitin', 'eq', 0);
                }
            }

            // Check if files have already been submitted and disable exclude biblio and quoted if turnitin is enabled.
            if ($cmid != 0) {
                if ($DB->record_exists('plagiarism_turnitin_files', array('cm' => $cmid))) {
                    $mform->disabledIf('plagiarism_exclude_biblio', 'use_turnitin');
                    $mform->disabledIf('plagiarism_exclude_quoted', 'use_turnitin');
                }

                if ($DB->record_exists('plagiarism_turnitin_config', array('cm' => $cmid, 'name' => 'submitted'))) {
                    $mform->disabledIf('plagiarism_anonymity', 'use_turnitin');
                }
            }

            // Set the default value for each option as the value we have stored.
            foreach ($plagiarismelements as $element) {
                if (isset($plagiarismvalues[$element])) {
                    $mform->setDefault($element, $plagiarismvalues[$element]);
                }
            }
        }
    }

    /**
     * Remove Turnitin class and assignment links from database
     * so that new classes and assignments will be created.
     *
     * @param type $eventdata
     * @return boolean
     */
    public static function course_reset(\core\event\course_reset_ended $event) {
        global $DB;
        $eventdata = $event->get_data();
        $courseid = (int)$eventdata['courseid'];
        $resetcourse = true;

        $resetassign = (isset($eventdata['other']['reset_options']['reset_assign_submissions'])) ?
                            $eventdata['other']['reset_options']['reset_assign_submissions'] : 0;
        $resetforum = (isset($eventdata['other']['reset_options']['reset_forum_all'])) ?
                            $eventdata['other']['reset_options']['reset_forum_all'] : 0;

        $supportedmods = array('assign', 'forum', 'workshop');
        foreach ($supportedmods as $supportedmod) {
            $module = $DB->get_record('modules', array('name' => $supportedmod));

            // Get all the course modules that have Turnitin enabled
            $sql = "SELECT cm.id
                    FROM {course_modules} cm
                    RIGHT JOIN {plagiarism_turnitin_config} ptc ON cm.id = ptc.cm
                    WHERE cm.module = :moduleid
                    AND cm.course = :courseid
                    AND ptc.name = 'turnitin_assignid'";
            $params = array('courseid' => $courseid, 'moduleid' => $module->id);
            $modules = $DB->get_records_sql($sql, $params);

            if (count($modules) > 0) {
                $reset = "reset".$supportedmod;
                if (!empty($$reset)) {
                    // Remove Plagiarism plugin submissions and assignment id from DB for this module.
                    foreach ($modules as $mod) {
                        $DB->delete_records('plagiarism_turnitin_files', array('cm' => $mod->id));
                        $DB->delete_records('plagiarism_turnitin_config', array('cm' => $mod->id, 'name' => 'turnitin_assignid'));
                    }
                } else {
                    $resetcourse = false;
                }
            }
        }

        // If all turnitin enabled modules for this course have been reset
        // then remove the Turnitin course id from the database
        if ($resetcourse) {
            $DB->delete_records('turnitintooltwo_courses', array('courseid' => $courseid, 'course_type' => 'PP'));
        }

        return true;
    }

    /**
     * Test whether we can connect to Turnitin.
     *
     * Initially only being used if a student is logged in before checking whether they have accepted the EULA.
     */
    public function test_turnitin_connection() {
        $turnitincomms = new turnitintooltwo_comms();
        $tiiapi = $turnitincomms->initialise_api();

        $class = new TiiClass();
        $class->setTitle('Test finding a class to see if connection works');

        try {
            $response = $tiiapi->findClasses($class);
            return true;
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'connecttesterror', false);
            return false;
        }
    }

    /**
     * Print the Turnitin student disclosure inside the submission page for students to see
     *
     * @global type $DB
     * @global type $OUTPUT
     * @param type $cmid
     * @return type
     */
    public function print_disclosure($cmid) {
        global $DB, $OUTPUT, $USER, $PAGE, $CFG;

        static $tiiconnection;
        if (empty($tiiconnection)) {
            $tiiconnection = $this->test_turnitin_connection();
        }

        $config = turnitintooltwo_admin_config();
        $output = '';

        // Get course details
        $cm = get_coursemodule_from_id('', $cmid);

        $configsettings = $this->get_config_settings('mod_'.$cm->modname);
        // Exit if Turnitin is not being used for this activity type.
        if (empty($configsettings['turnitin_use_mod_'.$cm->modname])) {
            return '';
        }

        $plagiarismsettings = $this->get_settings($cmid);
        // Check Turnitin is enabled for this current module.
        if (empty($plagiarismsettings['use_turnitin'])) {
            return '';
        }

        // Show agreement.
        if (!empty($config->agreement)) {
            $contents = format_text($config->agreement, FORMAT_MOODLE, array("noclean" => true));
            $output = $OUTPUT->box($contents, 'generalbox boxaligncenter', 'intro');
        }

        // Create the course/class in Turnitin if it doesn't already exist.
        $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP');
        if (empty($coursedata->turnitin_cid)) {
            // Course may existed in a previous incarnation of this plugin.
            // Get this and save it in courses table if so.
            if ($turnitincid = $this->get_previous_course_id($cm)) {
                $coursedata->turnitin_cid = $turnitincid;
                $coursedata = $this->migrate_previous_course($coursedata, $turnitincid);
            } else {
                // Otherwise create new course in Turnitin.
                $tiicoursedata = $this->create_tii_course($cm, $coursedata);
                $coursedata->turnitin_cid = $tiicoursedata->turnitin_cid;
                $coursedata->turnitin_ctl = $tiicoursedata->turnitin_ctl;
            }
        }

        // Show EULA if necessary and we have a connection to Turnitin.
        if ($tiiconnection) {
            $user = new turnitintooltwo_user($USER->id, "Learner");
            $user->join_user_to_class($coursedata->turnitin_cid);
            $eulaaccepted = (!$user->user_agreement_accepted) ? $user->get_accepted_user_agreement() : $user->user_agreement_accepted;

            if (!$eulaaccepted) {
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery-1.8.2.min.js');
                $PAGE->requires->js($jsurl);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/turnitintooltwo.js');
                $PAGE->requires->js($jsurl);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/plagiarism_plugin.js');
                $PAGE->requires->js($jsurl);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery.colorbox.js');
                $PAGE->requires->js($jsurl);

                // Moodle strips out form and script code for forum posts so we have to do the Eula Launch differently.
                $ula_link = html_writer::link($CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$cmid.'&cmd=useragreement&view_context=box_solid',
                                        get_string('turnitinppula', 'turnitintooltwo'),
                                        array("class" => "pp_turnitin_eula_link"));

                $ula = html_writer::tag('div', $ula_link, array('class' => 'pp_turnitin_ula js_required', 'data-userid' => $user->id));

                $noscriptula = html_writer::tag('noscript',
                                turnitintooltwo_view::output_dv_launch_form("useragreement", 0, $user->tii_user_id,
                                    "Learner", get_string('turnitinppula', 'turnitintooltwo'), false)." ".
                                        get_string('noscriptula', 'turnitintooltwo'),
                                            array('class' => 'warning turnitin_ula_noscript'));
            }

            // Show EULA launcher and form placeholder.
            if (!empty($ula)) {
                $output .= $ula.$noscriptula;

                $turnitincomms = new turnitintooltwo_comms();
                $turnitincall = $turnitincomms->initialise_api();

                $customdata = array("disable_form_change_checker" => true,
                                    "elements" => array(array('html', $OUTPUT->box('', '', 'useragreement_inputs'))));
                $eulaform = new turnitintooltwo_form($turnitincall->getApiBaseUrl().TiiLTI::EULAENDPOINT, $customdata,
                                                        'POST', $target = 'eulaWindow', array('id' => 'eula_launch'));
                $output .= $OUTPUT->box($eulaform->display(), 'tii_useragreement_form', 'useragreement_form');
            }
        }
        return $output;
    }

    private function get_max_files_allowed($moduleid, $modname) {
        global $DB;
        $filesallowed = 1;
        switch($modname) {
            case "assign":
                $moduledata = $DB->get_record('assign_plugin_config',
                                array('assignment' => $moduleid, 'name' => 'maxfilesubmissions'), 'value');
                $filesallowed = $moduledata->value;
                break;
            case "forum":
                $moduledata = $DB->get_record($modname, array('id' => $moduleid), 'maxattachments');
                $filesallowed = $moduledata->maxattachments;
                break;
            case "workshop":
                $moduledata = $DB->get_record($modname, array('id' => $moduleid), 'nattachments');
                $filesallowed = $moduledata->nattachments;
                break;
        }

        return $filesallowed;
    }

    /**
     *
     * @global type $CFG
     * @param type $linkarray
     * @return type
     */
    public function get_links($linkarray) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        // Don't submit feedback files to Turnitin.
        if (!empty($linkarray["file"])) {
            $file = $linkarray["file"];
            $filearea = $file->get_filearea();
            if ($filearea == "feedback_files") {
                return;
            }
        }

        // Set static variables.
        static $cm;
        static $forum;
        if (empty($cm)) {
            $cm = get_coursemodule_from_id('', $linkarray["cmid"]);

            if ($cm->modname == 'forum') {
                if (! $forum = $DB->get_record("forum", array("id" => $cm->instance))) {
                    print_error('invalidforumid', 'forum');
                }
            }
        }

        static $plagiarismsettings;
        if (empty($plagiarismsettings)) {
            $plagiarismsettings = $this->get_settings($linkarray["cmid"]);
            if ($cm->modname == 'assign') {
                $plagiarismsettings["plagiarism_draft_submit"] = (isset($plagiarismsettings["plagiarism_draft_submit"])) ?
                                                                    $plagiarismsettings["plagiarism_draft_submit"] : 0;
            }
        }

        // Exit if Turnitin is not being used for this module.
        if (empty($plagiarismsettings['use_turnitin'])) {
            return;
        }

        static $config;
        if (empty($config)) {
            $config = turnitintooltwo_admin_config();
        }

        static $configsettings;
        if (empty($configsettings)) {
            $configsettings = $this->get_config_settings('mod_'.$cm->modname);
        }

        // Exit if Turnitin is not being used for this activity type.
        if (empty($configsettings['turnitin_use_mod_'.$cm->modname])) {
            return;
        }

        static $moduledata;
        if (empty($moduledata)) {
            $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
        }

        static $context;
        if (empty($context)) {
            $context = context_course::instance($cm->course);
        }

        static $coursedata;
        if (empty($coursedata)) {
            // Create the course/class in Turnitin if it doesn't already exist.
            $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP');
            if (empty($coursedata->turnitin_cid)) {
                // Course may existed in a previous incarnation of this plugin.
                // Get this and save it in courses table if so.
                if ($turnitincid = $this->get_previous_course_id($cm)) {
                    $coursedata->turnitin_cid = $turnitincid;
                    $coursedata = $this->migrate_previous_course($coursedata, $turnitincid);
                } else {
                    // Otherwise create new course in Turnitin.
                    $tiicoursedata = $this->create_tii_course($cm, $coursedata);
                    $coursedata->turnitin_cid = $tiicoursedata->turnitin_cid;
                    $coursedata->turnitin_ctl = $tiicoursedata->turnitin_ctl;
                }
            }
        }

        // Work out if logged in user is a tutor on this module.
        static $istutor;
        switch ($cm->modname) {
            case "forum":
            case "workshop":
                if (empty($istutor)) {
                    $istutor = has_capability('plagiarism/turnitin:viewfullreport', $context);
                }
                break;
            default:
                if (empty($istutor)) {
                    $istutor = has_capability('mod/'.$cm->modname.':grade', $context);
                }
                break;
        }

        // Define the timestamp for updating Peermark Assignments.
        if (empty($_SESSION["updated_pm"][$cm->id]) && $config->enablepeermark) {
            $_SESSION["updated_pm"][$cm->id] = (time() - (60 * 5));
        }

        $output = "";

        // If a text submission has been made, we can only display links for current attempts so don't show links previous attempts.
        // This will need to be reworked when linkarray contains submission id.
        static $contentdisplayed;
        if ($cm->modname == 'assign' && !empty($linkarray["content"]) && $contentdisplayed == true) {
            return $output;
        }

        if ((!empty($linkarray["file"]) || !empty($linkarray["content"])) && !empty($linkarray["cmid"])) {

            // Include Javascript.
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery-1.8.2.min.js');
            $PAGE->requires->js($jsurl);
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/turnitintooltwo.js');
            $PAGE->requires->js($jsurl);
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/plagiarism_plugin.js');
            $PAGE->requires->js($jsurl);
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery.colorbox.js');
            $PAGE->requires->js($jsurl);
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery.tooltipster.js');
            $PAGE->requires->js($jsurl);

            // Initialise vars for working out whether we are submitting.
            $submitting = false;
            $submission_status = true;
            $identifier = '';

            // Get Assignment submission data and account for draft submissions which may not be submitted
            if ($cm->modname == 'assign') {
                if (!empty($linkarray["file"])){
                    $submission = $DB->get_record('assign_submission',
                                                array('userid' => $linkarray["userid"], 'assignment' => $moduledata->id,
                                                        'id' => $file->get_itemid()));
                } else {
                    // This will need to be reworked when linkarray contains submission id.
                    $submissions = $DB->get_records('assign_submission',
                                                array('userid' => $linkarray["userid"], 'assignment' => $moduledata->id));
                    $submission = end($submissions);
                }

                if ($submission) {
                    $submission_status = ($submission->status == "submitted" ||
                                        ($moduledata->submissiondrafts == 1 && $plagiarismsettings["plagiarism_draft_submit"] == 0))
                                        ? true : false;
                }
            }

            // Check whether a user's submission needs to be sent to Turnitin via Ajax.
            // Get File or Content information, for content only submit if it has been modified.
            $itemid = 0;
            if (!empty($linkarray["file"])) {
                $identifier = $file->get_pathnamehash();
                $itemid = $file->get_itemid();
                $submissiontype = 'file';
                $submitting = ($submission_status) ? true : false;
            } else if (!empty($linkarray["content"])) {

                // Get turnitin text content details.
                $submissiontype = ($cm->modname == "forum") ? 'forum_post' : 'text_content';
                switch ($cm->modname) {
                    case 'assign':
                        $moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
                                                    array('submission' => $submission->id), 'onlinetext');
                        if (empty($moodletextsubmission)) {
                            $content = '';
                            $submission_status = false;
                        } else {
                            $content = $moodletextsubmission->onlinetext;
                        }
                        break;
                    case 'workshop':
                        $submission = $DB->get_record('workshop_submissions',
                                                array('authorid' => $linkarray["userid"], 'workshopid' => $moduledata->id));
                        $content = $linkarray["content"];
                        break;
                    case 'forum':
                        static $discussionid;
                        // Use query string id to check whether we are on forum home page.
                        $querystrid = optional_param('id', 0, PARAM_INT);

                        // Work out the discussion id from query string.
                        if (empty($discussionid)) {
                            $discussionid = optional_param('d', 0, PARAM_INT);
                        }

                        if (empty($discussionid)) {
                            $reply   = optional_param('reply', 0, PARAM_INT);
                            $edit    = optional_param('edit', 0, PARAM_INT);
                            $delete  = optional_param('delete', 0, PARAM_INT);

                            $parent = '';
                            if ($reply != 0) {
                                $parent = forum_get_post_full($reply);
                            } else if ($edit != 0) {
                                $parent = forum_get_post_full($edit);
                            } else if ($delete != 0) {
                                $parent = forum_get_post_full($delete);
                            }

                            if (!empty($parent)) {
                                $discussionid = $parent->discussion;
                            }
                        }

                        // Some forum types don't pass in certain values on main forum page.
                        if ((empty($discussionid) || $querystrid != 0) &&
                            ($forum->type == 'blog' || $forum->type == 'single')) {
                            if (!$discussion = $DB->get_record_sql('SELECT FD.id
                                                                    FROM {forum_posts} FP JOIN {forum_discussions} FD
                                                                    ON FP.discussion = FD.id
                                                                    WHERE FD.forum = ? AND FD.course = ?
                                                                    AND FP.userid = ? AND FP.message LIKE ? ',
                                                                    array($forum->id, $forum->course,
                                                                        $linkarray["userid"], $linkarray["content"])
                                                                    )) {
                                print_error('notpartofdiscussion', 'forum');
                            }
                            $discussionid = $discussion->id;
                        }

                        $submission = $DB->get_record_select('forum_posts',
                                                " userid = ? AND message LIKE ? AND discussion = ? ",
                                                array($linkarray["userid"], $linkarray["content"], $discussionid));

                        $itemid = $submission->id;
                        $submission->timemodified = $submission->modified;
                        $content = $linkarray["content"];
                        break;
                }

                // Get plagiarism file info.
                $identifier = sha1($content);
                switch ($cm->modname) {
                    case 'assign':
                    case 'workshop':
                        $plagiarismfile = $DB->get_record_select('plagiarism_turnitin_files',
                                            " userid = ? AND cm = ? AND submissiontype = '".$submissiontype."' ",
                                                array($linkarray["userid"], $linkarray["cmid"]));
                        break;
                    case 'forum':
                        $plagiarismfile = $DB->get_record_select('plagiarism_turnitin_files',
                                            " userid = ? AND cm = ? AND identifier = ? AND submissiontype = '".$submissiontype."' ",
                                                array($linkarray["userid"], $linkarray["cmid"], $identifier));
                        break;
                }

                if (!empty($plagiarismfile)) {
                    $submitting = ($submission->timemodified > $plagiarismfile->lastmodified &&
                                        $plagiarismfile->identifier != $identifier) ? true : false;
                } else {
                    $submitting = true;
                }
            }

            // Group submissions where all students have to submit sets userid to 0;
            if ($linkarray['userid'] == 0 && !$istutor) {
                $linkarray['userid'] = $USER->id;
            }

            $output .= $OUTPUT->box_start('tii_links_container');

            // Show the EULA for a student if necessary.
            if ($linkarray["userid"] == $USER->id) {
                $eula = "";

                static $userid;
                if (empty($userid)) {
                    $userid = 0;
                }

                // Condition added to test for Moodle 2.7 as it calls this function twice.
                if ($CFG->branch >= 27 || $userid != $linkarray["userid"]) {
                    // Show EULA if necessary and we have a connection to Turnitin.

                    $user = new turnitintooltwo_user($USER->id, "Learner");
                    $success = $user->join_user_to_class($coursedata->turnitin_cid);

                    // $success is null if there is no Turnitin connection
                    if ($success) {
                        $eulaaccepted = (!$user->user_agreement_accepted) ? $user->get_accepted_user_agreement() : $user->user_agreement_accepted;
                        $userid = $linkarray["userid"];

                        if (!$eulaaccepted) {
                            $eula_link = html_writer::link($CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$linkarray["cmid"].
                                    '&cmd=useragreement&view_context=box_solid',
                                    get_string('turnitinppula', 'turnitintooltwo'),
                                    array("class" => "pp_turnitin_eula_link"));

                            $eula = html_writer::tag('div', $eula_link, array('class' => 'pp_turnitin_ula js_required', 'data-userid' => $user->id));
                            $submitting = false;
                        }

                        // Show EULA launcher and form placeholder.
                        if (!empty($eula)) {
                            $output .= $eula;

                            $turnitincomms = new turnitintooltwo_comms();
                            $turnitincall = $turnitincomms->initialise_api();

                            $customdata = array("disable_form_change_checker" => true,
                                    "elements" => array(array('html', $OUTPUT->box('', '', 'useragreement_inputs'))));
                            $eulaform = new turnitintooltwo_form($turnitincall->getApiBaseUrl().TiiLTI::EULAENDPOINT, $customdata,
                                    'POST', $target = 'eulaWindow', array('id' => 'eula_launch'));
                            $output .= $OUTPUT->box($eulaform->display(), 'tii_useragreement_form', 'useragreement_form');
                        }
                    }
                }

                // If a user has just submitted then send to Turnitin via Ajax.
                if ($submitting && $submission_status) {
                    // Include Javascript for Submitting.
                    $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/plagiarism_submission.js');
                    $PAGE->requires->js($jsurl);

                    // Forum posts have a lot of html stripped out so we have to get data to ajax differently
                    if ($submissiontype == 'forum_post') {
                        $output .= html_writer::start_tag('div', array('class' => 'plagiarism_submission'));
                        $output .= html_writer::tag('div', $identifier.'-'.$submissiontype,
                                                                array('class' => 'plagiarism_submission_id'));
                        $output .= html_writer::tag('div', urlencode($linkarray["content"]),
                                                                array('class' => 'plagiarism_submission_content'));
                        $output .= html_writer::tag('div', $linkarray["cmid"],
                                                            array('class' => 'plagiarism_submission_cmid'));
                        $output .= html_writer::tag('div', $itemid,
                                                                array('class' => 'plagiarism_submission_itemid'));
                        $output .= html_writer::end_tag('div');
                    } else {
                        $output .= html_writer::start_tag('div',
                                                        array('class' => 'plagiarism_submission',
                                                                'id' => $identifier.'-'.$submissiontype));
                        $output .= html_writer::tag('div', $linkarray["cmid"],
                                                            array('class' => 'plagiarism_submission_cmid'));
                        $output .= html_writer::tag('div', $itemid,
                                                                array('class' => 'plagiarism_submission_itemid'));
                        $output .= html_writer::end_tag('div');
                    }
                }
            }

            // Check whether submission is a group submission - only applicable to assignment module.
            // If it's a group submission then other users in the group should be able to see the originality score
            // They can not open the DV though.
            $submissionusers = array($linkarray["userid"]);
            if ($cm->modname == "assign" && $CFG->branch > 23) {
                if ($moduledata->teamsubmission) {
                    $assignment = new assign($context, $cm, null);
                    if ($group = $assignment->get_submission_group($linkarray["userid"])) {
                        $users = groups_get_members($group->id);
                        $submissionusers = array_keys($users);
                    }
                }
            }

            // Display Links for files and contents.
            if ((!empty($linkarray["file"]) || !empty($linkarray["content"])) &&
                    ($istutor || ($submission_status && in_array($USER->id, $submissionusers)))) {

                // Prevent text content links being displayed for previous attempts as we have no way of getting the data.
                if (!empty($linkarray["content"]) && $linkarray["userid"] == $USER->id) {
                    $contentdisplayed = true;
                }

                // Get turnitin details - have to do this again as submission may have been made above.
                $plagiarismfiles = $DB->get_records('plagiarism_turnitin_files', array('userid' => $linkarray["userid"],
                                                        'cm' => $linkarray["cmid"], 'identifier' => $identifier),
                                                        'lastmodified DESC', '*', 0, 1);
                $plagiarismfile = current($plagiarismfiles);

                // Get user's grades.
                $postdate = 0;
                $currentgradequery = false;
                if ($istutor || $linkarray["userid"] == $USER->id) {
                    if ($cm->modname == 'forum') {
                        static $gradeitem;
                        if (empty($gradeitem)) {
                            $gradeitem = $DB->get_record('grade_items',
                                            array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname, 'courseid' => $cm->course));
                        }
                        if ($gradeitem) {
                            $currentgradequery = $DB->get_record('grade_grades',
                                                        array('userid' => $linkarray["userid"], 'itemid' => $gradeitem->id));
                        }
                    } else if ($cm->modname == 'workshop') {
                        static $gradeitem;
                        if (empty($gradeitem)) {
                            $gradeitem = $DB->get_record('grade_items',
                                    array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname, 'itemnumber' => 0, 'courseid' => $cm->course));
                        }
                        if ($gradeitem) {
                            $currentgradequery = $DB->get_record('grade_grades', array('userid' => $linkarray["userid"], 'itemid' => $gradeitem->id));
                        }
                        $postdate = $moduledata->assessmentend;
                    } else if ($cm->modname == 'assign') {
                        static $gradeitem;
                        if (empty($gradeitem)) {
                            $gradeitem = $DB->get_record('grade_items',
                                            array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname, 'courseid' => $cm->course));
                        }
                        $postdate = 0;
                        if ($gradeitem) {
                            $currentgradesquery = $DB->get_records('assign_grades',
                                                    array('userid' => $linkarray["userid"], 'assignment' => $cm->instance), 'id DESC');
                            $currentgradequery = current($currentgradesquery);

                            $postdate = ($gradeitem->hidden != 1) ? $gradeitem->hidden : strtotime('+1 month');
                        }
                    }
                }

                if ($plagiarismfile) {
                    if ($plagiarismfile->statuscode == 'success') {
                        if ($istutor || $linkarray["userid"] == $USER->id) {
                            $output .= html_writer::tag('div',
                                            $OUTPUT->pix_icon('icon-sml',
                                                get_string('turnitinid', 'turnitintooltwo').': '.$plagiarismfile->externalid, 'mod_turnitintooltwo',
                                                array('class' => 'turnitin_paper_id')).
                                                get_string('turnitinid', 'turnitintooltwo').': '.$plagiarismfile->externalid);
                        }

                        // Show Originality Report score and link.
                        if (($istutor || (in_array($USER->id, $submissionusers) && $plagiarismsettings["plagiarism_show_student_report"])) &&
                            ((is_null($plagiarismfile->orcapable) || $plagiarismfile->orcapable == 1) && !is_null($plagiarismfile->similarityscore))) {

                            // This class is applied so that only the user who submitted or a tutor can open the DV.
                            $useropenclass = ($USER->id == $linkarray["userid"] || $istutor) ? 'pp_origreport_open' : '';
                            $output .= $OUTPUT->box_start('row_score pp_origreport '.$useropenclass.' origreport_'.
                                                            $plagiarismfile->externalid.'_'.$linkarray["cmid"],
                                                            $CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$linkarray["cmid"]);

                            // Show score.
                            if ($plagiarismfile->statuscode == "pending") {
                                $output .= html_writer::tag('div', '&nbsp;', array('title' => get_string('pending', 'turnitintooltwo'),
                                                                        'class' => 'tii_tooltip origreport_score score_colour score_colour_'));
                            } else {
                                // Put EN flag if translated matching is on and that is the score used.
                                $transmatch = ($plagiarismfile->transmatch == 1) ? ' EN' : '';

                                if (is_null($plagiarismfile->similarityscore)) {
                                    $score = '&nbsp;';
                                    $titlescore = get_string('pending', 'turnitintooltwo');
                                    $class = 'score_colour_';
                                } else {
                                    $score = $plagiarismfile->similarityscore.'%';
                                    $titlescore = $plagiarismfile->similarityscore.'% '.get_string('similarity', 'turnitintooltwo');
                                    $class = 'score_colour_'.round($plagiarismfile->similarityscore, -1);
                                }

                                $output .= html_writer::tag('div', $score.$transmatch,
                                                array('title' => $titlescore, 'class' => 'tii_tooltip origreport_score score_colour '.$class));
                            }
                            // Put in div placeholder for DV launch form.
                            $output .= $OUTPUT->box('', 'launch_form origreport_form_'.$plagiarismfile->externalid);
                            // Add url for launching DV from Forum post.
                            if ($cm->modname == 'forum') {
                                $output .= $OUTPUT->box($CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$linkarray["cmid"],
                                                        'origreport_forum_launch origreport_forum_launch_'.$plagiarismfile->externalid);
                            }
                            $output .= $OUTPUT->box_end(true);
                        }

                        if (($plagiarismfile->orcapable == 0 && !is_null($plagiarismfile->orcapable))) {
                            // This class is applied so that only the user who submitted or a tutor can open the DV.
                            $useropenclass = ($USER->id == $linkarray["userid"] || $istutor) ? 'pp_origreport_open' : '';

                            $output .= $OUTPUT->box_start('row_score pp_origreport '.$useropenclass, '');
                            $output .= html_writer::tag('div', 'x', array('title' => get_string('notorcapable', 'turnitintooltwo'),
                                                                        'class' => 'tii_tooltip score_colour score_colour_ score_no_orcapable'));
                            $output .= $OUTPUT->box_end(true);
                        }

                        // Show link to open grademark.
                        if ($config->usegrademark &&
                            ($istutor || ($linkarray["userid"] == $USER->id && !is_null($plagiarismfile->grade)
                                            && ($postdate != 1 && $postdate <= time()) && !empty($currentgradequery))) && !empty($gradeitem)) {

                            // Output grademark icon.
                            $output .= $OUTPUT->box_start('grade_icon', '');
                            $output .= html_writer::tag('div', $OUTPUT->pix_icon('icon-edit',
                                                                get_string('grademark', 'turnitintooltwo'), 'mod_turnitintooltwo'),
                                                    array('title' => get_string('grademark', 'turnitintooltwo'),
                                                        'class' => 'pp_grademark_open tii_tooltip grademark_'.$plagiarismfile->externalid.
                                                                        '_'.$linkarray["cmid"],
                                                        'id' => $CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$linkarray["cmid"]));

                            // Add url for launching DV from Forum post.
                            if ($cm->modname == 'forum') {
                                $output .= $OUTPUT->box($CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$linkarray["cmid"],
                                                        'grademark_forum_launch grademark_forum_launch_'.$plagiarismfile->externalid);
                            }
                            // Put in div placeholder for DV launch form.
                            $output .= $OUTPUT->box('', 'launch_form grademark_form_'.$plagiarismfile->externalid);
                            $output .= $OUTPUT->box_end(true);
                        }

                        if (!$istutor && $config->usegrademark && !empty($plagiarismsettings["plagiarism_rubric"])) {
                            $rubricviewlink = html_writer::tag('div', html_writer::link(
                                                            $CFG->wwwroot.'/plagiarism/turnitin/ajax.php?cmid='.$cm->id.
                                                                    '&action=rubricview&view_context=box', '&nbsp;',
                                                            array('class' => 'tii_tooltip rubric_view_pp_launch', 'id' => 'rubric_view_launch',
                                                                    'title' => get_string('launchrubricview', 'turnitintooltwo'))).
                                                                        html_writer::tag('span', '',
                                                                        array('class' => 'launch_form', 'id' => 'rubric_view_form')),
                                                            array('class' => 'row_rubric_view'));
                            $output .= $rubricviewlink;
                        }

                        if ($config->enablepeermark) {
                            // If this module is already on Turnitin then refresh and get Peermark Assignments.
                            if (!empty($plagiarismsettings['turnitin_assignid'])) {
                                if ($_SESSION["updated_pm"][$cm->id] <= (time() - (60 * 2))) {
                                    $this->refresh_peermark_assignments($cm, $plagiarismsettings['turnitin_assignid']);
                                    $turnitintooltwoassignment = new turnitintooltwo_assignment($cm->instance, '', 'PP');
                                    $_SESSION["peermark_assignments"][$cm->id] =
                                                        $turnitintooltwoassignment->get_peermark_assignments($plagiarismsettings['turnitin_assignid']);
                                    $_SESSION["updated_pm"][$cm->id] = time();
                                }

                                // Determine if we have any active Peermark Assignments.
                                static $peermarksactive;
                                if (!isset($peermarksactive)) {
                                    $peermarksactive = false;
                                    foreach ($_SESSION["peermark_assignments"][$cm->id] as $peermarkassignment) {
                                        if (time() > $peermarkassignment->dtstart) {
                                            $peermarksactive = true;
                                            break;
                                        }
                                    }
                                }

                                // Show Peermark Reviews link.
                                if (($istutor && count($_SESSION["peermark_assignments"][$cm->id]) > 0) ||
                                                            (!$istutor && $peermarksactive)) {
                                    $peermarkreviewslink = $OUTPUT->box_start('row_peermark_reviews', '');
                                    $peermarkreviewslink .= html_writer::link($CFG->wwwroot.'/plagiarism/turnitin/ajax.php?cmid='.$cm->id.
                                                                '&action=peermarkreviews&view_context=box', '',
                                                                array('title' => get_string('launchpeermarkreviews', 'turnitintooltwo'),
                                                                    'class' => 'peermark_reviews_pp_launch tii_tooltip'));
                                    $peermarkreviewslink .= html_writer::tag('span', '', array('class' => 'launch_form',
                                                                                                'id' => 'peermark_reviews_form'));
                                    $peermarkreviewslink .= $OUTPUT->box_end(true);

                                    $output .= $peermarkreviewslink;
                                }
                            }
                        }
                    } else if ($plagiarismfile->statuscode == 'error') {

                        // Deal with legacy error issues.
                        if (isset($plagiarismfile->errorcode)) {
                            $errorcode = 0;
                            if ($submissiontype == 'file') {
                                if ($file->get_filesize() > TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE) {
                                    $errorcode = 2;
                                }
                            }
                        } else {
                            $errorcode = $plagiarismfile->errorcode;
                        }

                        // Show error message if there is one.
                        if ($errorcode == 0) {
                            $langstring = ($istutor) ? 'ppsubmissionerrorseelogs' : 'ppsubmissionerrorstudent';
                            $errorstring = (isset($plagiarismfile->errormsg)) ?
                                                get_string($langstring, 'turnitintooltwo') : $plagiarismfile->errormsg;
                        } else {
                            $errorstring = get_string('errorcode'.$plagiarismfile->errorcode,
                                            'turnitintooltwo', display_size(TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE));
                        }

                        $erroricon = html_writer::tag('div', $OUTPUT->pix_icon('x-red', $errorstring, 'mod_turnitintooltwo'),
                                                                array('title' => $errorstring,
                                                                        'class' => 'tii_tooltip tii_error_icon'));
                        $output .= html_writer::tag('div', $erroricon, array('class' => 'clear'));
                    }

                } else {
                    // Add Error to show that user has not accepted EULA.
                    if (($linkarray["userid"] != $USER->id) && $istutor) {
                        // There is a moodle plagiarism bug where get_links is called twice, the first loop is incorrect and is killing
                        // this functionality. Have to check that user exists here first else there will be a fatal error.
                        if ($mdl_user = $DB->get_record('user', array('id' => $linkarray["userid"]))) {
                            // We also need to check for security that they are actually on the Course.
                            switch ($cm->modname) {
                                case 'assign':
                                case 'workshop':
                                    $capability = 'mod/'.$cm->modname.':submit';
                                    break;
                                case 'forum':
                                    $capability = 'mod/'.$cm->modname.':replypost';
                                    break;
                            }
                            if (has_capability($capability, $context, $linkarray["userid"])) {
                                $user = new turnitintooltwo_user($linkarray["userid"], "Learner");
                                if (!$user->user_agreement_accepted) {
                                    $erroricon = html_writer::tag('div', $OUTPUT->pix_icon('doc-x-grey', get_string('notacceptedeula', 'turnitintooltwo'),
                                                                            'mod_turnitintooltwo'),
                                                                            array('title' => get_string('notacceptedeula', 'turnitintooltwo'),
                                                                                    'class' => 'tii_tooltip tii_error_icon'));
                                    $output .= html_writer::tag('div', $erroricon, array('class' => 'clear'));
                                }
                            }
                        }
                    }
                }

                // Show error warning for submission
                $output .= html_writer::tag('div', '', array('class' =>
                                                'turnitin_submit_error warning clear turnitin_submit_error_'.$identifier));

                // Show success of submission
                $output .= html_writer::tag('div', '', array('class' =>
                                                'turnitin_submit_success success clear turnitin_submit_success_'.$identifier));
                $output .= html_writer::tag('div', '', array('class' => 'clear'));
            }

            $output .= $OUTPUT->box_end(true);
        }

        return $output;
    }

    public function update_grades_from_tii($cm) {
        global $DB;
        $plagiarismvalues = $this->get_settings($cm->id);
        $submissionids = array();
        $return = true;

        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
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

            $submissionids = $findsubmission->getSubmissionIds();
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
            $return = false;
        }

        // Refresh updated submissions.
        if (count($submissionids) > 0) {
            // Process submissions in batches, depending on the max. number of submissions the Turnitin API returns.
            $submissionbatches = array_chunk($submissionids, PLAGIARISM_TURNITIN_NUM_RECORDS_RETURN);
            foreach ($submissionbatches as $submissionsbatch) {
                try {
                    $submission = new TiiSubmission();
                    $submission->setSubmissionIds($submissionsbatch);

                    $response = $turnitincall->readSubmissions($submission);
                    $readsubmissions = $response->getSubmissions();

                    foreach ($readsubmissions as $readsubmission) {
                        $submissiondata = $DB->get_record('plagiarism_turnitin_files',
                                                            array('externalid' => $readsubmission->getSubmissionId()), 'id');

                        $return = $this->update_submission($cm, $submissiondata->id, $readsubmission);
                    }

                } catch (Exception $e) {
                    $turnitincomms->handle_exceptions($e, 'tiisubmissiongeterror', false);
                    $return = false;
                }
            }
        }

        return $return;
    }

    public function update_grade_from_tii($cm, $submissionid) {
        global $DB;
        $return = true;

        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $submission = new TiiSubmission();
            $submission->setSubmissionId($submissionid);

            $response = $turnitincall->readSubmission($submission);

            $readsubmission = $response->getSubmission();

            $this->update_submission($cm, $submissionid, $readsubmission);

        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
            $return = false;
        }

        return $return;
    }

    private function update_submission($cm, $submissionid, $tiisubmission) {
        global $DB;

        $return = true;
        $updaterequired = false;

        if ($submissiondata = $DB->get_record('plagiarism_turnitin_files', array('id' => $submissionid),
                                                 'id, cm, userid, similarityscore, grade, orcapable')) {
            $plagiarismfile = new object();
            $plagiarismfile->id = $submissiondata->id;
            $plagiarismfile->similarityscore = (is_numeric($tiisubmission->getOverallSimilarity())) ?
                                                    $tiisubmission->getOverallSimilarity() : null;
            $plagiarismfile->transmatch = 0;
            if ((int)$tiisubmission->getTranslatedOverallSimilarity() > $tiisubmission->getOverallSimilarity()) {
                $plagiarismfile->similarityscore = $tiisubmission->getTranslatedOverallSimilarity();
                $plagiarismfile->transmatch = 1;
            }
            $plagiarismfile->grade = ($tiisubmission->getGrade() == '') ? null : $tiisubmission->getGrade();
            $plagiarismfile->orcapable = ($tiisubmission->getOriginalityReportCapable() == 1) ? 1 : 0;

            // Identify if an update is required for the similarity score and grade.
            if (!is_null($plagiarismfile->similarityscore) || !is_null($plagiarismfile->grade) ||
                    !is_null($plagiarismfile->orcapable)) {
                if ($submissiondata->similarityscore != $plagiarismfile->similarityscore ||
                        $submissiondata->grade != $plagiarismfile->grade ||
                        $submissiondata->orcapable != $plagiarismfile->orcapable) {
                    $updaterequired = true;
                }
            }

            // Only update as necessary.
            if ($updaterequired) {
                $DB->update_record('plagiarism_turnitin_files', $plagiarismfile);

                if ($cm->modname == 'forum' || $cm->modname == 'assign') {
                    $gradeitem = $DB->get_record('grade_items',
                                    array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname, 'courseid' => $cm->course));
                } else if ($cm->modname == 'workshop') {
                    $gradeitem = $DB->get_record('grade_items',
                                    array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname, 'courseid' => $cm->course, 'itemnumber' => 0));
                }

                if (!is_null($plagiarismfile->grade) && !empty($gradeitem)) {
                    $return = $this->update_grade($cm, $tiisubmission, $submissiondata->userid);
                }
            }
        }

        return $return;
    }

    /**
     * Update module grade and gradebook.
     */
    private function update_grade($cm, $submission, $userid, $type = 'submission') {
        global $DB, $USER, $CFG;
        $return = true;
        $grade = $submission->getGrade();

        if (!is_null($grade) && $cm->modname != 'forum') {

            // Module grade object.
            $grade = new stdClass();
            // If submission has multiple content/files in it then get average grade.
            // Ignore NULL grades and files no longer part of submission.

            switch ($cm->modname) {
                case 'assign':
                    $component = 'assignsubmission_file';
                    break;
                default:
                    $component = 'mod_'.$cm->modname;
                    break;
            }

            // Get file from pathname hash
            $submissiondata = $DB->get_record('plagiarism_turnitin_files', array('externalid' => $submission->getSubmissionId()), 'identifier');

            // Get file as we need item id for discounting files that are no longer in submission.
            $fs = get_file_storage();
            if ($file = $fs->get_file_by_hash($submissiondata->identifier)) {
                $moodlefiles = $DB->get_records_select('files', " component = ? AND userid = ? AND itemid = ? AND source IS NOT null ",
                                                    array($component, $userid, $file->get_itemid()), 'id DESC', 'pathnamehash');

                list($insql, $inparams) = $DB->get_in_or_equal(array_keys($moodlefiles), SQL_PARAMS_QM, 'param', true);
                $tiisubmissions = $DB->get_records_select('plagiarism_turnitin_files', " userid = ? AND cm = ? AND identifier ".$insql,
                                                        array_merge(array($userid, $cm->id), $inparams));
            } else {
                $tiisubmissions = $DB->get_records('plagiarism_turnitin_files', array('userid' => $userid, 'cm' => $cm->id));
                $tiisubmissions = current($tiisubmissions);
            }

            if (count($tiisubmissions)) {
                $averagegrade = null;
                $gradescounted = 0;
                $tiisubmissions = $DB->get_records('plagiarism_turnitin_files', array('userid' => $userid, 'cm' => $cm->id));
                foreach ($tiisubmissions as $tiisubmission) {
                    if (!is_null($tiisubmission->grade)) {
                        $averagegrade = $averagegrade + $tiisubmission->grade;
                        $gradescounted += 1;
                    }
                }
                $grade->grade = (!is_null($averagegrade) && $gradescounted > 0) ? (int)($averagegrade / $gradescounted) : null;
            } else {
                $grade->grade = $submission->getGrade();
            }

            // Check whether submission is a group submission - only applicable to assignment module.
            // If it's a group submission we will update the grade for everyone in the group.
            // Note: This will not work if the submitting user is in multiple groups.
            $userids = array($userid);
            if ($cm->modname == "assign" && $CFG->branch > 23) {
                $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
                if ($moduledata->teamsubmission) {
                    require_once($CFG->dirroot . '/mod/assign/locallib.php');
                    $context = context_course::instance($cm->course);
                    $assignment = new assign($context, $cm, null);

                    if ($group = $assignment->get_submission_group($userid)) {
                        $users = groups_get_members($group->id);
                        $userids = array_keys($users);
                    }
                }
            }

            // Loop through all users and update grade
            foreach ($userids as $userid) {
                // Get gradebook data.
                switch ($cm->modname) {
                    case 'assign':
                        $currentgrades = $DB->get_records('assign_grades', array('userid' => $userid, 'assignment' => $cm->instance), 'id DESC');
                        $currentgrade = current($currentgrades);
                        break;
                    case 'workshop':
                        if ($gradeitem = $DB->get_record('grade_items', array('iteminstance' => $cm->instance,
                                                        'itemmodule' => $cm->modname, 'itemnumber' => 0))) {
                            $currentgrade = $DB->get_record('grade_grades', array('userid' => $userid, 'itemid' => $gradeitem->id));
                        }
                        break;
                }

                switch ($cm->modname) {
                    case 'workshop':
                        if ($currentgrade) {
                            $grade->id = $currentgrade->id;
                        } else {
                            $grade->userid = $userid;
                            $grade->itemid = $gradeitem->id;
                            $grade->timecreated = time();
                            $grade->usermodified = $USER->id;
                        }
                        $table = 'grade_grades';
                        break;

                    case 'assign':
                        if ($currentgrade) {
                            $grade->id = $currentgrade->id;
                        } else {
                            $grade->userid = $userid;
                            $grade->assignment = $cm->instance;
                            $grade->timecreated = time();
                            $grade->grader = $USER->id;
                        }
                        $table = $cm->modname.'_grades';
                        break;
                }
                $grade->timemodified = time();

                // Insert/Update grade for this assignment.
                if ($currentgrade) {
                    if (!$DB->update_record($table, $grade)) {
                        $return = false;
                    }
                } else if ($grade) {
                    if (!$DB->insert_record($table, $grade)) {
                        $return = false;
                    }
                }

                // Gradebook object.
                if ($grade) {
                    $grades = new stdClass();
                    $grades->userid = $userid;
                    $grades->rawgrade = $grade->grade;
                    $params['idnumber'] = $cm->idnumber;

                    // Update gradebook - Grade update returns 1 on failure and 0 if successful.
                    if (grade_update('mod/'.$cm->modname, $cm->course, 'mod', $cm->modname, $cm->instance, 0, $grades, $params)) {
                        $return = false;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Create a course within Turnitin
     */
    public function create_tii_course($cm, $coursedata, $workflowcontext = "site") {
        global $CFG;

        switch ($cm->modname) {
            case "forum":
            case "workshop":
                $capability = 'plagiarism/turnitin:viewfullreport';
                break;
            default:
                $capability = 'mod/'.$cm->modname.':grade';
                break;
        }

        $tutors = get_users_by_capability(context_module::instance($cm->id), $capability, 'u.id', 'u.id');

        // If no tutors on this course then use main admin as owner.
        if (!empty($tutors)) {
            $owner = current($tutors);
            $ownerid = $owner->id;
        } else {
            $admins = explode(",", $CFG->siteadmins);
            $ownerid = $admins[0];
        }

        $turnitintooltwoassignment = new turnitintooltwo_assignment(0, '', 'PP');
        $turnitincourse = $turnitintooltwoassignment->create_tii_course($coursedata, $ownerid, "PP", $workflowcontext);

        // Join all admins to the course in Turnitin.
        $admins = explode(",", $CFG->siteadmins);
        foreach ($admins as $admin) {
            // Create the admin as a user within Turnitin.
            $user = new turnitintooltwo_user($admin, 'Instructor');
            $user->join_user_to_class($turnitincourse->turnitin_cid);
        }

        // Join all tutors to the course in Turnitin.
        if (!empty($tutors)) {
            foreach ($tutors as $tutor) {
                // Create the admin as a user within Turnitin.
                $user = new turnitintooltwo_user($admin, 'Instructor');
                $user->join_user_to_class($turnitincourse->turnitin_cid);
            }
        }

        return $turnitincourse;
    }

    /**
     * Get Peermark Assignments for this module from Turnitin.
     */
    public function refresh_peermark_assignments($cm, $tiiassignmentid) {
        global $DB;

        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
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
                    $peermark = new object();
                    $peermark->tiiassignid = $peermarkassignment->getAssignmentId();
                    $peermark->parent_tii_assign_id = $readassignment->getAssignmentId();
                    $peermark->dtstart = strtotime($peermarkassignment->getStartDate());
                    $peermark->dtdue = strtotime($peermarkassignment->getDueDate());
                    $peermark->dtpost = strtotime($peermarkassignment->getFeedbackReleaseDate());
                    $peermark->maxmarks = (int)$peermarkassignment->getMaxGrade();
                    $peermark->title = $peermarkassignment->getTitle();
                    $peermark->instructions = $peermarkassignment->getInstructions();
                    $peermark->distributed_reviews = (int)$peermarkassignment->getDistributedReviews();
                    $peermark->selected_reviews = (int)$peermarkassignment->getSelectedReviews();
                    $peermark->self_review = (int)$peermarkassignment->getSelfReviewRequired();
                    $peermark->non_submitters_review = (int)$peermarkassignment->getNonSubmittersToReview();

                    $currentpeermark = $DB->get_record('turnitintooltwo_peermarks',
                                                array('tiiassignid' => $peermark->tiiassignid));

                    if ($currentpeermark) {
                        $peermark->id = $currentpeermark->id;
                        $DB->update_record('turnitintooltwo_peermarks', $peermark);
                    } else {
                        $DB->insert_record('turnitintooltwo_peermarks', $peermark);
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
     */
    public function sync_tii_assignment($cm, $coursetiiid, $workflowcontext = "site") {
        global $DB;

        $config = turnitintooltwo_admin_config();
        $modulepluginsettings = $this->get_settings($cm->id);
        $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));

        // Configure assignment object to send to Turnitin.
        $assignment = new TiiAssignment();
        $assignment->setClassId($coursetiiid);
        // Need to truncate the moodle assignment title to be compatible with a Turnitin class (max length 99)
        $assignment->setTitle( mb_strlen( $moduledata->name ) > 80 ? mb_substr( $moduledata->name, 0, 80 ) . "... (Moodle PP)"
                            : $moduledata->name . " (Moodle PP)" );
        $assignment->setSubmitPapersTo(isset($modulepluginsettings["plagiarism_submitpapersto"]) ?
                                            $modulepluginsettings["plagiarism_submitpapersto"] : 1);
        $assignment->setSubmittedDocumentsCheck($modulepluginsettings["plagiarism_compare_student_papers"]);
        $assignment->setInternetCheck($modulepluginsettings["plagiarism_compare_internet"]);
        $assignment->setPublicationsCheck($modulepluginsettings["plagiarism_compare_journals"]);
        if ($config->repositoryoption == 1) {
            $institutioncheck = (isset($modulepluginsettings["plagiarism_compare_institution"])) ?
                                        $modulepluginsettings["plagiarism_compare_institution"] : 0;
            $assignment->setInstitutionCheck($institutioncheck);
        }

        $assignment->setAuthorOriginalityAccess($modulepluginsettings["plagiarism_show_student_report"]);
        $assignment->setResubmissionRule($modulepluginsettings["plagiarism_report_gen"]);
        $assignment->setBibliographyExcluded($modulepluginsettings["plagiarism_exclude_biblio"]);
        $assignment->setQuotedExcluded($modulepluginsettings["plagiarism_exclude_quoted"]);
        $assignment->setSmallMatchExclusionType($modulepluginsettings["plagiarism_exclude_matches"]);
        $modulepluginsettings["plagiarism_exclude_matches_value"] =
                        (!empty($modulepluginsettings["plagiarism_exclude_matches_value"])) ?
                                $modulepluginsettings["plagiarism_exclude_matches_value"] : 0;

        $assignment->setSmallMatchExclusionThreshold($modulepluginsettings["plagiarism_exclude_matches_value"]);
        if ($config->useanon) {
            $assignment->setAnonymousMarking($modulepluginsettings["plagiarism_anonymity"]);
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

        if (!empty($moduledata->allowsubmissionsfromdate)) {
            $dtstart = $moduledata->allowsubmissionsfromdate;
        } else if (!empty($moduledata->timeavailable)) {
            $dtstart = $moduledata->timeavailable;
        } else {
            $dtstart = $cm->added;
        }
        $dtstart = ($dtstart <= strtotime('-1 year')) ? strtotime('-11 months') : $dtstart;
        $assignment->setStartDate(gmdate("Y-m-d\TH:i:s\Z", $dtstart));

        $dtdue = 0;
        if (!empty($moduledata->duedate)) {
            $dtdue = $moduledata->duedate;
            if (isset($moduledata->cutoffdate)) {
                if ($moduledata->cutoffdate > 0) {
                    $dtdue = $moduledata->cutoffdate;
                }
            }
        } else if (!empty($moduledata->timedue)) {
            $dtdue = $moduledata->timedue;
        }

        // If the due date has been set more than a year ahead then restrict the
        // due date in Turnitin to 1 year from now.
        $nextyear = strtotime('+1 year');
        if ($dtdue > $nextyear) {
            $dtdue = $nextyear;
        }

        // If the module has no due date or is a forum, or the due date has passed,
        // we make the due date one day from now in Turnitin so that we can submit past the due date.
        if ($dtdue <= time()) {
            $dtdue = strtotime('+1 day');
        }

        // Set post date. If "hidden until" has been set in gradebook then we will use that value, otherwise we will
        // use start date. If the grades are to be completely hidden then we will set post date in the future.
        $dtpost = 0;
        if ($cm->modname != "forum") {
            if ($gradeitem = $DB->get_record('grade_items',
                            array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname, 'courseid' => $cm->course))) {
                switch ($gradeitem->hidden) {
                    case 1:
                        $dtpost = strtotime('+6 months');
                        break;
                    case 0:
                        $dtpost = $dtstart;
                        break;
                    default:
                        $dtpost = $gradeitem->hidden;
                        break;
                }
            }
        }
        // Ensure due date can't be before start date
        if ($dtdue <= $dtstart) {
            $dtdue = strtotime('+1 month', $dtstart);
        }
        // Ensure post date can't be before start date
        if ($dtpost < $dtstart) {
            $dtpost = $dtstart;
        }
        $assignment->setDueDate(gmdate("Y-m-d\TH:i:s\Z", $dtdue));
        $assignment->setFeedbackReleaseDate(gmdate("Y-m-d\TH:i:s\Z", $dtpost));

        // Erater settings.
        $assignment->setErater((isset($modulepluginsettings["plagiarism_erater"])) ?
                                                $modulepluginsettings["plagiarism_erater"] : 0);
        $assignment->setEraterSpelling((isset($modulepluginsettings["plagiarism_erater_spelling"])) ?
                                                $modulepluginsettings["plagiarism_erater_spelling"] : 0);
        $assignment->setEraterGrammar((isset($modulepluginsettings["plagiarism_erater_grammar"])) ?
                                                $modulepluginsettings["plagiarism_erater_grammar"] : 0);
        $assignment->setEraterUsage((isset($modulepluginsettings["plagiarism_erater_usage"])) ?
                                                $modulepluginsettings["plagiarism_erater_usage"] : 0);
        $assignment->setEraterMechanics((isset($modulepluginsettings["plagiarism_erater_mechanics"])) ?
                                                $modulepluginsettings["plagiarism_erater_mechanics"] : 0);
        $assignment->setEraterStyle((isset($modulepluginsettings["plagiarism_erater_style"])) ?
                                                $modulepluginsettings["plagiarism_erater_style"] : 0);
        $assignment->setEraterSpellingDictionary((isset($modulepluginsettings["plagiarism_erater_dictionary"])) ?
                                                $modulepluginsettings["plagiarism_erater_dictionary"] : 'en_US');
        $assignment->setEraterHandbook((isset($modulepluginsettings["plagiarism_erater_handbook"])) ?
                                                $modulepluginsettings["plagiarism_erater_handbook"] : 0);

        // If we have a turnitin id then edit the assignment otherwise create it.
        if ($tiiassignment = $DB->get_record('plagiarism_turnitin_config',
                                    array('cm' => $cm->id, 'name' => 'turnitin_assignid'), 'value')) {
            $assignment->setAssignmentId($tiiassignment->value);
            $turnitintooltwoassignment = new turnitintooltwo_assignment(0, '', 'PP');
            $turnitintooltwoassignment->edit_tii_assignment($assignment, $workflowcontext);

            $turnitinassignid = $tiiassignment->value;
        } else {
            $turnitintooltwoassignment = new turnitintooltwo_assignment(0, '', 'PP');
            $turnitinassignid = $turnitintooltwoassignment->create_tii_assignment($assignment, 0, 0, 'plagiarism_plugin', $workflowcontext);

            $moduleconfigvalue = new stdClass();
            $moduleconfigvalue->cm = $cm->id;
            $moduleconfigvalue->name = 'turnitin_assignid';
            $moduleconfigvalue->value = $turnitinassignid;
            $DB->insert_record('plagiarism_turnitin_config', $moduleconfigvalue);
        }

        // Save the post date we have stored in Turnitin
        $moduleconfigvalue = new stdClass();
        $moduleconfigvalue->cm = $cm->id;
        $moduleconfigvalue->name = 'plagiarism_post_date';
        $moduleconfigvalue->value = $dtpost;

        if ($plagiarism_post_date = $DB->get_record('plagiarism_turnitin_config',
                                    array('cm' => $cm->id, 'name' => 'plagiarism_post_date'), 'id')) {

            $moduleconfigvalue->id = $plagiarism_post_date->id;
            $DB->update_record('plagiarism_turnitin_config', $moduleconfigvalue);
        } else {
            $DB->insert_record('plagiarism_turnitin_config', $moduleconfigvalue);
        }

        return $turnitinassignid;
    }

    /**
     * Check whether we need to submit the file to Turnitin. If the file has not previously been submitted then submit it.
     * If text content gets this far then it needs to be submitted
     *
     * @param object $user the user who the submission is for
     * @param string $pathnamehash to identify the file to be submitted
     */
    public function check_if_submitting($cm, $userid, $pathnamehash, $submissiontype) {
        global $DB;

        if ($submissiontype == 'text_content' || $submissiontype == 'forum_post') {
            return true;
        } else if ($previoussubmissions = $DB->get_records_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND identifier = ? ",
                                                array($cm->id, $userid, $pathnamehash), 'id DESC',
                                                    'id, cm, externalid, identifier, statuscode, lastmodified', 0, 1)) {

            // Get the file data to check date modified.
            $fs = get_file_storage();
            $file = $fs->get_file_by_hash($pathnamehash);

            $currentsubmission = current($previoussubmissions);
            if ($currentsubmission->identifier == $pathnamehash && $currentsubmission->statuscode == "success"
                && $file->get_timemodified() <= $currentsubmission->lastmodified) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Call functions to be run by cron
     */
    public function cron() {
        $this->cron_update_assignments();

        // Update scores by separate submission type.
        $submissiontypes = array('file', 'text_content', 'forum_post');
        foreach ($submissiontypes as $submissiontype) {
            $this->cron_update_scores($submissiontype);
        }
        return true;
    }

    /**
     * This is used to update assignments, specifically post dates in Turnitin which can be changed in Gradebook.
     */
    public function cron_update_assignments() {
        global $DB;

        $assignments = $DB->get_records_select('plagiarism_turnitin_config',
                                        " name = ? ", array('turnitin_assignid'), 'cm, value');

        foreach ($assignments as $assignment) {
            $cm = get_coursemodule_from_id('', $assignment->cm);

            if ($cm) {
                $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));

                // Don't update for forums as post date will be start date in this instance as there is no gradebook.
                if ($cm->modname != 'forum') {
                    // Get course data.
                    $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP', 'cron');
                    if (empty($coursedata->turnitin_cid)) {
                        // Course may existed in a previous incarnation of this plugin.
                        // Get this and save it in courses table if so.
                        if ($turnitincid = $this->get_previous_course_id($cm)) {
                            $coursedata = $this->migrate_previous_course($coursedata, $turnitincid);
                        } else {
                            // Otherwise create new course in Turnitin.
                            $tiicoursedata = $this->create_tii_course($cm, $coursedata, "cron");
                            if (empty($tiicoursedata)) {
                                continue;
                            }
                            $coursedata->turnitin_cid = $tiicoursedata->turnitin_cid;
                            $coursedata->turnitin_ctl = $tiicoursedata->turnitin_ctl;
                        }
                    }

                    // Only update modules that haven't started yet.
                    $dtstart = 0;
                    if (!empty($moduledata->allowsubmissionsfromdate)) {
                        $dtstart = $moduledata->allowsubmissionsfromdate;
                    } else if (!empty($moduledata->timeavailable)) {
                        $dtstart = $moduledata->timeavailable;
                    } else {
                        $dtstart = $cm->added;
                    }
                    if ($dtstart > time()) {
                        continue;
                    }

                    if ($plagiarism_post_date = $DB->get_record_select('plagiarism_turnitin_config',
                                                " name = ? AND cm = ? ", array('plagiarism_post_date', $cm->id), 'value')) {

                        $post_date = $plagiarism_post_date->value;
                        if ($gradeitem = $DB->get_record('grade_items', array('iteminstance' => $cm->instance,
                                                        'itemmodule' => $cm->modname, 'itemnumber' => 0, 'courseid' => $cm->course))) {

                            // 1 means grade is always hidden, 0 means it's never hidden so we make it the same as start date.
                            // Otherwise there is a hidden until date which we use as the post date.
                            switch ($gradeitem->hidden) {
                                case 1:
                                    // If Turnitin post date is in the next 7 days then push it ahead
                                    if ($post_date < (time() + (60 * 60 * 24 * 7)))  {
                                        $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");
                                    }
                                    break;
                                case 0:
                                    if ($post_date > time()) {
                                        $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");
                                    }
                                    break;
                                default:
                                    if ($post_date != $gradeitem->hidden) {
                                        $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");
                                    }
                                    break;
                            }
                        }
                    } else {
                        $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update simliarity scores.
     *
     * @return boolean
     */
    public function cron_update_scores($submissiontype = 'file') {
        global $DB;

        $submissions = $DB->get_records_select('plagiarism_turnitin_files',
                                        " statuscode = ? AND submissiontype = ? AND similarityscore IS NULL AND ( orcapable = ? OR orcapable IS NULL ) ",
                                        array('success', $submissiontype, 1), 'externalid DESC');
        $submissionids = array();
        $reportsexpected = array();

        // Add submission ids to the request.
        foreach ($submissions as $tiisubmission) {
            // Only add the submission to the request if the module still exists.
            if ($cm = get_coursemodule_from_id('', $tiisubmission->cm)) {
                if (!isset($reportsexpected[$cm->id])) {
                    $plagiarismsettings = $this->get_settings($cm->id);
                    $reportsexpected[$cm->id] = 1;

                    if (!isset($plagiarismsettings['plagiarism_compare_institution'])) {
                        $plagiarismsettings['plagiarism_compare_institution'] = 0;
                    }

                    // Don't add the submission to the request if module settings mean we will not get a report back.
                    if ($plagiarismsettings['plagiarism_compare_student_papers'] == 0 &&
                        $plagiarismsettings['plagiarism_compare_internet'] == 0 &&
                        $plagiarismsettings['plagiarism_compare_journals'] == 0 &&
                        $plagiarismsettings['plagiarism_compare_institution'] == 0) {
                        $reportsexpected[$cm->id] = 0;
                    }
                }

                // Only add the submission to the request if we are expecting an originality report.
                if ($reportsexpected[$cm->id] == 1) {
                    $submissionids[] = $tiisubmission->externalid;
                }
            }
        }

        if (count($submissionids) > 0) {

            // Process submissions in batches, depending on the max. number of submissions the Turnitin API returns.
            $submissionbatches = array_chunk($submissionids, PLAGIARISM_TURNITIN_NUM_RECORDS_RETURN);
            foreach ($submissionbatches as $submissionsbatch) {

                // Initialise Comms Object.
                $turnitincomms = new turnitintooltwo_comms();
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

                            $currentsubmission = $DB->get_record('plagiarism_turnitin_files', array('externalid' => $tiisubmissionid),
                                                                                                    'id, cm, externalid, userid');
                            if ($cm = get_coursemodule_from_id('', $currentsubmission->cm)) {

                                $plagiarismfile = new object();
                                $plagiarismfile->id = $currentsubmission->id;
                                $plagiarismfile->externalid = $tiisubmissionid;
                                $plagiarismfile->similarityscore = (is_numeric($readsubmission->getOverallSimilarity())) ?
                                                                                $readsubmission->getOverallSimilarity() : null;
                                $plagiarismfile->grade = (is_numeric($readsubmission->getGrade())) ? $readsubmission->getGrade() : null;
                                $plagiarismfile->orcapable = ($readsubmission->getOriginalityReportCapable() == 1) ? 1 : 0;
                                $plagiarismfile->transmatch = 0;
                                if (is_int($readsubmission->getTranslatedOverallSimilarity()) &&
                                        $readsubmission->getTranslatedOverallSimilarity() > $readsubmission->getOverallSimilarity()) {
                                    $plagiarismfile->similarityscore = $readsubmission->getTranslatedOverallSimilarity();
                                    $plagiarismfile->transmatch = 1;
                                }

                                if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                                    mtrace("File failed to update: ".$plagiarismfile->id);
                                } else {
                                    mtrace("File updated: ".$plagiarismfile->id);
                                }

                                if (!is_null($plagiarismfile->grade)) {
                                    $this->update_grade($cm, $readsubmission, $currentsubmission->userid);
                                }
                            }
                        } catch (Exception $e) {
                            mtrace("An exception was thrown while attempting to read submission $tiisubmissionid: "
                                   . $e->getMessage() . '(' . $e->getFile() . ':' . $e->getLine() . ')');
                        }

                    }
                } catch (Exception $e) {
                    mtrace(get_string('tiisubmissionsgeterror', 'turnitintooltwo'));
                    $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
                    // Do not return false if a batch fails - another one might work.
                }
            }
        }

        return true;
    }

    /**
     * Get a class Id from Turnitin if you only have an assignment id.
     */
    private function get_course_id_from_assignment_id($assignmentid) {
        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $assignment = new TiiAssignment();
            $assignment->setAssignmentId($assignmentid);

            $response = $turnitincall->readAssignment($assignment);
            $readassignment = $response->getAssignment();

            return $readassignment->getClassId();
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'assigngeterror', false);
        }
    }

    /**
     * Previous incarnations of this plugin did not store the turnitin course id so we have to get this using the assignment id,
     * If that wasn't linked with turnitin then we have to check all the modules on this course.
     */
    public function get_previous_course_id($cm) {
        global $DB;
        $courseid = 0;

        if ($tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cm->id,
                                                    'name' => 'turnitin_assignid'))) {
            $courseid = $this->get_course_id_from_assignment_id($tiiassignment->value);
        } else {
            $coursemods = get_course_mods($cm->course);
            foreach ($coursemods as $coursemod) {
                if ($coursemod->modname != 'turnitintooltwo') {
                    if ($tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $coursemod->id,
                                                                                        'name' => 'turnitin_assignid'))) {
                        $courseid = $this->get_course_id_from_assignment_id($tiiassignment->value);
                    }
                }
            }
        }

        return ($courseid > 0) ? $courseid : false;
    }

    /**
     * Migrate course from previous version of plugin to this
     */
    public function migrate_previous_course($coursedata, $turnitincid, $workflowcontext = "site") {
        global $DB, $USER;

        $turnitincourse = new object();
        $turnitincourse->courseid = $coursedata->id;
        $turnitincourse->ownerid = $USER->id;
        $turnitincourse->turnitin_cid = $turnitincid;
        $turnitincourse->turnitin_ctl = $coursedata->fullname . " (Moodle PP)";
        $turnitincourse->course_type = 'PP';

        if (empty($coursedata->tii_rel_id)) {
            $method = "insert_record";
        } else {
            $method = "update_record";
            $turnitincourse->id = $coursedata->tii_rel_id;
        }

        if (!$insertid = $DB->$method('turnitintooltwo_courses', $turnitincourse)) {
            if ($workflowcontext != "cron") {
                turnitintooltwo_print_error('classupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                exit();
            }
        }

        $turnitintooltwoassignment = new turnitintooltwo_assignment(0, '', 'PP');
        $turnitintooltwoassignment->edit_tii_course($coursedata, "PP");

        $coursedata->turnitin_cid = $turnitincid;
        $coursedata->turnitin_ctl = $turnitincourse->turnitin_ctl;

        return $coursedata;
    }

    /**
     * Amalgamated handler for Moodle cron events.
     *
     * @param object $eventdata
     * @return bool result
     */
    public function event_handler($eventdata) {
        global $DB, $CFG;
        $cm = get_coursemodule_from_id($eventdata->modulename, $eventdata->cmid);

        // Initialise plugin class.
        $plagiarismsettings = $this->get_settings($eventdata->cmid);
        if ($eventdata->modulename == 'assign') {
            $plagiarismsettings["plagiarism_draft_submit"] = (isset($plagiarismsettings["plagiarism_draft_submit"])) ?
                                                                $plagiarismsettings["plagiarism_draft_submit"] : 0;
        }

        // This module isn't using Turnitin so return true to remove event from queue.
        if (empty($plagiarismsettings['use_turnitin'])) {
            return true;
        }

        $configsettings = $this->get_config_settings('mod_'.$eventdata->modulename);
        // Exit if Turnitin is not being used for this activity type.
        if (empty($configsettings['turnitin_use_mod_'.$eventdata->modulename])) {
            return;
        }

        static $tiiconnection;
        if (empty($tiiconnection)) {
            if (!$tiiconnection = $this->test_turnitin_connection()) {
                return false;
            }
        }

        if ($cm) {
            // Create the course/class in Turnitin if it doesn't already exist.
            $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP', 'cron');
            if (empty($coursedata->turnitin_cid)) {
                // Course may existed in a previous incarnation of this plugin.
                // Get this and save it in courses table if so.
                if ($turnitincid = $this->get_previous_course_id($cm)) {
                    $coursedata = $this->migrate_previous_course($coursedata, $turnitincid);
                } else {
                    // Otherwise create new course in Turnitin.
                    $tiicoursedata = $this->create_tii_course($cm, $coursedata, "cron");
                    if (empty($tiicoursedata)) {
                        return false;
                    }
                    $coursedata->turnitin_cid = $tiicoursedata->turnitin_cid;
                    $coursedata->turnitin_ctl = $tiicoursedata->turnitin_ctl;
                }
            }

            switch ($eventdata->event_type) {
                case "mod_created":
                case "mod_updated":
                    $result = $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");
                    break;

                case "file_uploaded":
                case "assessable_submitted":
                case "content_uploaded":
                case "files_done":
                    $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
                    if ($cm->modname != 'assign') {
                        $moduledata->submissiondrafts = 0;
                    }

                    // If draft submissions are turned on then only submit to Turnitin if using newer than 2.3 and
                    // the Turnitin draft submit setting is set.
                    if ($moduledata->submissiondrafts && $CFG->branch > 23 &&
                        $plagiarismsettings["plagiarism_draft_submit"] == 1 &&
                        ($eventdata->event_type == 'file_uploaded' || $eventdata->event_type == 'content_uploaded')) {
                        return true;
                    }

                    // Join User to course.
                    $user = new turnitintooltwo_user($eventdata->userid, 'Learner');
                    $user->join_user_to_class($coursedata->turnitin_cid);

                    // Don't submit and remove from queue if a user has not accepted the eula.
                    if (!$user->user_agreement_accepted) {
                        mtrace('-------------------------');
                        mtrace(get_string('notacceptedeula', 'turnitintooltwo'));
                        mtrace(get_string('eventremoved', 'turnitintooltwo').':');
                        mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
                        mtrace('Course Module: '.$cm->id);
                        mtrace('-------------------------');
                        return true;
                    }

                    $tiiassignmentid = $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");

                    if ((int)$tiiassignmentid > 0) {
                        // Submit draft content and files for newer than 2.3.
                        if ($eventdata->modulename == 'assign' &&
                            ($eventdata->event_type == "files_done" || $eventdata->event_type == "assessable_submitted")) {

                            // Get content.
                            $moodlesubmission = $DB->get_record('assign_submission', array('assignment' => $cm->instance,
                                                        'userid' => $eventdata->userid, 'id' => $eventdata->itemid), 'id');
                            if ($moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
                                                        array('submission' => $moodlesubmission->id), 'onlinetext')) {
                                $eventdata->content = $moodletextsubmission->onlinetext;
                            }

                            // Get Files.
                            $eventdata->pathnamehashes = array();
                            $filesconditions = array('component' => 'assignsubmission_file',
                                                    'itemid' => $moodlesubmission->id, 'userid' => $eventdata->userid);
                            if ($moodlefiles = $DB->get_records('files', $filesconditions)) {
                                foreach ($moodlefiles as $moodlefile) {
                                    $eventdata->pathnamehashes[] = $moodlefile->pathnamehash;
                                }
                            }
                        }

                        // Submit content.
                        $result = true;
                        if (($eventdata->event_type == "content_uploaded" || $eventdata->event_type == "files_done" ||
                                $eventdata->event_type == "assessable_submitted")
                                && !empty($eventdata->content)) {

                            switch ($eventdata->modulename) {
                                case "assign":
                                    if ($contentsubmission = $DB->get_record('assign_submission', array('userid' => $user->id,
                                                                                'assignment' => $moduledata->id,
                                                                                'id' => $eventdata->itemid))) {
                                        $timemodified = $contentsubmission->timemodified;
                                        $tempfilename = 'onlinetext_'.$user->id."_".$cm->id."_".$moduledata->id.'.txt';
                                        $submissiontype = 'text_content';
                                    } else {
                                        // Content has been deleted but event not removed.
                                        return true;
                                    }

                                    break;

                                case "forum":
                                    if ($contentsubmission = $DB->get_record('forum_posts', array('id' => $eventdata->itemid))) {
                                        $timemodified = $contentsubmission->modified;
                                        $tempfilename = 'forumpost_'.$user->id."_".$cm->id."_".
                                                            $moduledata->id."_".$eventdata->itemid.'.txt';
                                        $submissiontype = 'forum_post';
                                    } else {
                                        // Content has been deleted but event not removed.
                                        return true;
                                    }

                                    break;
                            }

                            $identifier = sha1($eventdata->content);

                            // Get turnitin text content details.
                            $plagiarismfile = $DB->get_record('plagiarism_turnitin_files', array('userid' => $user->id, 'cm' => $cm->id,
                                                                                            'identifier' => $identifier));
                            $tiimodifieddate = (!empty($plagiarismfile)) ? $plagiarismfile->lastmodified : 0;

                            if ($timemodified > $tiimodifieddate) {
                                $result = $this->tii_submission($cm, $tiiassignmentid, $user, $identifier, $submissiontype,
                                                                    $eventdata->itemid, $tempfilename, $eventdata->content, 'cron');
                            } else {
                                $result = true;
                            }
                        }

                        // Submit files.
                        $result = $result && true;
                        if (!empty($eventdata->pathnamehashes)) {
                            foreach ($eventdata->pathnamehashes as $pathnamehash) {
                                $fs = get_file_storage();
                                $file = $fs->get_file_by_hash($pathnamehash);

                                if (!$file) {
                                    turnitintooltwo_activitylog('File not found: '.$pathnamehash, 'PP_NO_FILE');
                                    $result = true;
                                    continue;
                                }

                                if ($file->get_filename() === '.') {
                                    continue;
                                }

                                // Don't process anything submitted in the last minute in case it's submitting still.
                                if ($file->get_timemodified() > time() - 60) {
                                    $result = false;
                                }

                                if ($this->check_if_submitting($cm, $eventdata->userid, $pathnamehash, 'file')) {
                                    $result = $result && $this->tii_submission($cm, $tiiassignmentid, $user, $pathnamehash, 'file',
                                                                                $eventdata->itemid, '', '', 'cron');
                                } else {
                                    $result = $result && true;
                                }
                            }
                        }
                    } else {
                        mtrace('-------------------------');
                        mtrace(get_string('pp_assignmentsubmiterror', 'turnitintooltwo').':');
                        mtrace('Course Module: '.$cm->id);
                        mtrace('-------------------------');
                        $result = false;
                    }

                    break;
            }

            return $result;
        }
    }

    /**
     * Initialise submission values
     *
     **/
    private function create_new_tii_submission($cm, $user, $identifier, $submissiontype) {
        global $DB;

        $plagiarismfile = new object();
        $plagiarismfile->cm = $cm->id;
        $plagiarismfile->userid = $user->id;
        $plagiarismfile->identifier = $identifier;
        $plagiarismfile->statuscode = "pending";
        $plagiarismfile->similarityscore = null;
        $plagiarismfile->attempt = 1;
        $plagiarismfile->transmatch = 0;
        $plagiarismfile->submissiontype = $submissiontype;

        if (!$fileid = $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
            turnitintooltwo_activitylog("Insert record failed (CM: ".$cm->id.", User: ".$user->id.")", "PP_NEW_SUB");
            $fileid = 0;
        }

        return $fileid;
    }

    /**
     * Reset submission values
     *
     **/
    private function reset_tii_submission($cm, $user, $identifier, $currentsubmission, $submissiontype) {
        global $DB;

        $plagiarismfile = new object();
        $plagiarismfile->id = $currentsubmission->id;
        $plagiarismfile->identifier = $identifier;
        $plagiarismfile->statuscode = "pending";
        $plagiarismfile->similarityscore = null;
        if ($currentsubmission->statuscode != "error") {
            $plagiarismfile->attempt = 1;
        }
        $plagiarismfile->transmatch = 0;
        $plagiarismfile->submissiontype = $submissiontype;
        $plagiarismfile->orcapable = null;

        if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
            turnitintooltwo_activitylog("Update record failed (CM: ".$cm->id.", User: ".$user->id.")", "PP_REPLACE_SUB");
        }
    }

    /**
     * Clean up previous file submissions.
     * Moodle will remove any old files or drafts during cron execution and file submission.
     */
    private function clean_old_turnitin_submissions($cm, $userid, $itemid, $submissiontype, $identifier) {
        global $DB;
        $currentfiles = array();
        $deletestr = '';

        // Get all the files that are currently associated with this submission.
        switch ($cm->modname) {
            case 'assign':
                $component = 'assignsubmission_file';
                break;
            default:
                $component = 'mod_'.$cm->modname;
                break;
        }

        if ($submissiontype == 'file') {
            if ($moodlefiles = $DB->get_records_select('files', " component = ? AND userid = ? AND itemid = ? AND source IS NOT null ",
                                                    array($component, $userid, $itemid), 'id DESC', 'pathnamehash')) {
                list($notinsql, $notinparams) = $DB->get_in_or_equal(array_keys($moodlefiles), SQL_PARAMS_QM, 'param', false);
                $oldfiles = $DB->get_records_select('plagiarism_turnitin_files', " userid = ? AND cm = ? ".
                                                                            " AND submissiontype = 'file' AND identifier ".$notinsql,
                                                        array_merge(array($userid, $cm->id), $notinparams));

                if (!empty($oldfiles)) {
                    // Initialise Comms Object.
                    $turnitincomms = new turnitintooltwo_comms();
                    $turnitincall = $turnitincomms->initialise_api();

                    foreach ($oldfiles as $oldfile) {
                        // Delete submission from Turnitin if we have an external id.
                        if (!is_null($oldfile->externalid)) {
                            $this->delete_tii_submission($oldfile->externalid);
                        }
                        $deletestr .= $oldfile->id.', ';
                    }

                    list($insql, $deleteparams) = $DB->get_in_or_equal(explode(',', substr($deletestr, 0, -2)));
                    $deletestr = " id ".$insql;
                }
            }

        } else if ($submissiontype == 'text_content') {
            $deletestr = " userid = ? AND cm = ? AND submissiontype = 'text_content' AND identifier != ? ";
            $deleteparams = array($userid, $cm->id, $identifier);
        }

        // Delete from database.
        if (!empty($deletestr)) {
            $DB->delete_records_select('plagiarism_turnitin_files', $deletestr, $deleteparams);
        }
    }

    /**
     * If there is no submission record then we are creating one. Text content should be submitted.
     * If a file has already been submitted then check whether the identifier is the same, if it is do nothing.
     * If it's not then either edit submission or create new one depending on module settings.
     */
    public function tii_submission($cm, $tiiassignmentid, $user, $identifier, $submissiontype, $itemid = 0,
                                    $title = '', $textcontent = '', $context = 'instant') {
        global $CFG, $DB, $USER;

        $settings = $this->get_settings($cm->id);
        $nooffilesallowed = $this->get_max_files_allowed($cm->instance, $cm->modname);

        // Do not submit if 5 attempts have been made previously.
        $previoussubmissions = $DB->get_records_select('plagiarism_turnitin_files',
                                        " cm = ? AND userid = ? AND submissiontype = ? AND identifier = ? ",
                                    array($cm->id, $user->id, $submissiontype, $identifier), 'id, attempt');
        $previoussubmission = current($previoussubmissions);
        $attempt = (empty($previoussubmission)) ? 0 : $previoussubmission->attempt;

        if (count($previoussubmissions) >= 5 || $attempt >= 5) {
            if ($context == 'cron') {
                mtrace('-------------------------');
                mtrace(get_string('pastfiveattempts', 'turnitintooltwo').':');
                mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
                mtrace('Course Module: '.$cm->id.'');
                mtrace('-------------------------');

                return true;
            }
            $return["success"] = false;
            $return["message"] = get_string('pastfiveattempts', 'turnitintooltwo');
            return $return;
        }

        // Update user's details on Turnitin.
        $user->edit_tii_user();

        // Clean up old Turnitin submission files. This will only run on cron execution or for ajax file submissions.
        if ($itemid != 0 && $submissiontype == 'file' && $cm->modname != 'forum') {
            $this->clean_old_turnitin_submissions($cm, $user->id, $itemid, $submissiontype, $identifier);
        }

        // Work out submission method.
        // If this file has successfully submitted in the past then break, text content is to be submitted.
        switch ($submissiontype) {
            case 'file':
            case 'text_content':

                // Get file data or prepare text submission.
                if ($submissiontype == 'file') {
                    $fs = get_file_storage();
                    $file = $fs->get_file_by_hash($identifier);

                    $title = $file->get_filename();
                    $timemodified = $file->get_timemodified();
                    $filename = $file->get_filename();
                    $textcontent = $file->get_content();
                } else {
                    // If we are submitting text_content via AJAX there will be no actual content passed in so we need to grab it.
                    if ($textcontent == '') {
                        switch ($cm->modname) {
                            case 'assign':
                                // Check whether submission is a group submission so we can get the correct content.
                                // Note: This will not work if the submitting user is in multiple groups.
                                $submissionsquery = array('assignment' => $cm->instance);
                                $submissionsquery['userid'] = $user->id;
                                if ($CFG->branch > 23) {
                                    $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
                                    if ($moduledata->teamsubmission) {
                                        require_once($CFG->dirroot . '/mod/assign/locallib.php');
                                        $context = context_course::instance($cm->course);
                                        $assignment = new assign($context, $cm, null);

                                        $submissionsquery['userid'] = 0;
                                        $submissionsquery['groupid'] = 0;
                                        if ($group = $assignment->get_submission_group($user->id)) {
                                            $submissionsquery['groupid'] = $group->id;
                                        }
                                    }
                                }

                                // This will need to be reworked when linkarray in get_links() contains submission id.
                                $moodlesubmissions = $DB->get_records('assign_submission', $submissionsquery, 'id, timemodified');
                                $moodlesubmission = end($moodlesubmissions);
                                $moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
                                                            array('submission' => $moodlesubmission->id), 'onlinetext');
                                $timemodified = $moodlesubmission->timemodified;
                                $textcontent = strip_tags($moodletextsubmission->onlinetext);
                                break;
                            case 'workshop':
                                $moodlesubmission = $DB->get_record('workshop_submissions',
                                                        array('workshopid' => $cm->instance,
                                                                'authorid' => $user->id), 'title, content, timemodified');
                                $timemodified = $moodlesubmission->timemodified;
                                $textcontent = strip_tags($moodlesubmission->content);
                                $title = $moodlesubmission->title;
                                break;
                        }
                    } else {
                        switch ($cm->modname) {
                            case 'assign':
                                $moodlesubmission = $DB->get_record('assign_submission',
                                                        array('assignment' => $cm->instance,
                                                                    'userid' => $user->id,
                                                                    'id' => $itemid), 'id, timemodified');
                                $timemodified = $moodlesubmission->timemodified;
                                $textcontent = strip_tags($textcontent);
                                break;
                            case 'workshop':
                                $moodlesubmission = $DB->get_record('workshop_submissions',
                                                        array('workshopid' => $cm->instance,
                                                                'authorid' => $user->id), 'title, content, timemodified');
                                $timemodified = $moodlesubmission->timemodified;
                                $textcontent = strip_tags($textcontent);
                                break;
                        }
                    }

                    $title = (!empty($title)) ? $title : 'onlinetext_'.$user->id."_".$cm->id."_".$cm->instance.'.txt';
                    $filename = (substr($title, -4) == '.txt') ? $title : $title.'.txt';
                }

                // Get submission method depending on whether there has been a previous submission.
                if ($previoussubmission = $DB->get_record_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND submissiontype = ? AND identifier = ? ",
                                                array($cm->id, $user->id, $submissiontype, $identifier),
                                                    'id, cm, externalid, identifier, statuscode, lastmodified', 0, 1)) {

                    // Don't submit if submission hasn't changed.
                    if ($previoussubmission->statuscode == "success" &&
                            (($submissiontype == 'file' && $timemodified <= $previoussubmission->lastmodified)
                                || $submissiontype != 'file')) {
                        return true;
                    } else if ($settings["plagiarism_report_gen"] > 0) {
                        // Replace if Turnitin assignment allows resubmissions or create if we have no Turnitin id stored.
                        $submissionid = $previoussubmission->id;
                        $apimethod = (is_null($previoussubmission->externalid)) ? "createSubmission" : "replaceSubmission";
                        $this->reset_tii_submission($cm, $user, $identifier, $previoussubmission, $submissiontype);
                    } else {
                        $apimethod = "createSubmission";
                        if ($previoussubmission->statuscode != "success") {
                            $submissionid = $previoussubmission->id;
                            $this->reset_tii_submission($cm, $user, $identifier, $previoussubmission, $submissiontype);
                        } else {
                            $submissionid = $this->create_new_tii_submission($cm, $user, $identifier, $submissiontype);
                        }
                    }

                } else {
                    // Check if there is previous submission of text content which we will replace
                    if ($submissiontype == 'text_content' &&
                            $previoussubmission = $DB->get_record_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND submissiontype = 'text_content' ",
                                                array($cm->id, $user->id),
                                                    'id, cm, externalid, identifier, statuscode, lastmodified', 0, 1)) {

                        $submissionid = $previoussubmission->id;
                        $apimethod = (is_null($previoussubmission->externalid) || $settings["plagiarism_report_gen"] == 0)
                                            ? "createSubmission" : "replaceSubmission";

                        // Delete old text content submissions from Turnitin if not replacing.
                        if ($settings["plagiarism_report_gen"] == 0 && !is_null($previoussubmission->externalid)) {
                            $this->delete_tii_submission($previoussubmission->externalid);
                        }

                        $this->reset_tii_submission($cm, $user, $identifier, $previoussubmission, $submissiontype);
                    } else {
                        $apimethod = "createSubmission";
                        $submissionid = $this->create_new_tii_submission($cm, $user, $identifier, $submissiontype);
                    }
                }

                // Remove any old text submissions here if there are any as there is only one per submission
                if ($itemid != 0 && $submissiontype == "text_content") {
                    $this->clean_old_turnitin_submissions($cm, $user->id, $itemid, $submissiontype, $identifier);
                }

                break;

            case 'forum_post':
                if ($previoussubmissions = $DB->get_records_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND identifier = ? ",
                                                    array($cm->id, $user->id, $identifier),
                                                    'id DESC', 'id, cm, externalid, identifier, statuscode', 0, 1)) {

                    $previoussubmission = current($previoussubmissions);
                    if ($previoussubmission->statuscode == "success") {
                        return true;
                    } else {
                        $submissionid = $previoussubmission->id;
                        $apimethod = "replaceSubmission";
                        $this->reset_tii_submission($cm, $user, $identifier, $previoussubmission, $submissiontype);
                    }
                } else {
                    $apimethod = "createSubmission";
                    $submissionid = $this->create_new_tii_submission($cm, $user, $identifier, $submissiontype);
                }

                $forum_post = $DB->get_record_select('forum_posts', " userid = ? AND id = ? ", array($user->id, $itemid));
                $textcontent = strip_tags($forum_post->message);

                $filename = $title;
                break;
        }

        // Do not submit if this is text_content and we're not accepting anything and
        // content is less than 20 words or 100 characters.
        if ($submissiontype != 'file') {
            $content = explode(' ', $textcontent);
            if ($settings['plagiarism_allow_non_or_submissions'] != 1 &&
                    (strlen($textcontent) < 100 || count($content) < 20)) {
                $plagiarismfile = new object();
                if ($submissionid != 0) {
                    $plagiarismfile->id = $submissionid;
                }
                $plagiarismfile->cm = $cm->id;
                $plagiarismfile->userid = $user->id;
                $plagiarismfile->identifier = $identifier;
                $plagiarismfile->statuscode = 'error';
                $plagiarismfile->errorcode = 1;
                $plagiarismfile->attempt = 1;
                $plagiarismfile->lastmodified = time();
                $plagiarismfile->submissiontype = $submissiontype;

                if ($submissionid != 0) {
                    if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                        turnitintooltwo_activitylog("Update record failed (CM: ".$cm->id.", User: ".$user->id.") - ", "PP_UPDATE_SUB_ERROR");
                    }
                } else {
                    if (!$fileid = $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
                        turnitintooltwo_activitylog("Insert record failed (CM: ".$cm->id.", User: ".$user->id.") - ", "PP_INSERT_SUB_ERROR");
                    }
                }

                if ($context == 'cron') {
                    mtrace('-------------------------');
                    mtrace(get_string('errorcode1', 'turnitintooltwo').':');
                    mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
                    mtrace('Course Module: '.$cm->id.'');
                    mtrace('-------------------------');

                    return true;
                }

                $return["success"] = false;
                $return["message"] = get_string('errorcode1', 'turnitintooltwo');
                return $return;
            }
        }

        // Check file is less than maximum allowed size.
        if ($submissiontype == 'file') {
            if ($file->get_filesize() > TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE) {
                $plagiarismfile = new object();
                if ($submissionid != 0) {
                    $plagiarismfile->id = $submissionid;
                }
                $plagiarismfile->cm = $cm->id;
                $plagiarismfile->userid = $user->id;
                $plagiarismfile->identifier = $identifier;
                $plagiarismfile->statuscode = 'error';
                $plagiarismfile->errorcode = 2;
                $plagiarismfile->attempt = 1;
                $plagiarismfile->lastmodified = time();
                $plagiarismfile->submissiontype = 'file';

                if ($submissionid != 0) {
                    if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                        turnitintooltwo_activitylog("Update record failed (CM: ".$cm->id.", User: ".$user->id.") - ", "PP_UPDATE_SUB_ERROR");
                    }
                } else {
                    if (!$fileid = $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
                        turnitintooltwo_activitylog("Insert record failed (CM: ".$cm->id.", User: ".$user->id.") - ", "PP_INSERT_SUB_ERROR");
                    }
                }

                if ($context == 'cron') {
                    mtrace('-------------------------');
                    mtrace(get_string('errorcode2', 'turnitintooltwo').':');
                    mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
                    mtrace('Course Module: '.$cm->id.'');
                    mtrace('-------------------------');

                    return true;
                }

                $return["success"] = false;
                $return["message"] = get_string('errorcode2', 'turnitintooltwo', display_size(TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE));
                return $return;
            }
        }

        // Read the stored file/content into a temp file for submitting.
        $submission_title = explode('.', $title);

        $file_string = array(
            $submission_title[0],
            $cm->id
        );

        $modulepluginsettings = $this->get_settings($cm->id);

        if ( ! $modulepluginsettings["plagiarism_anonymity"]) {
            $user_details = array(
                $user->id,
                $user->firstname,
                $user->lastname
            );

            $file_string = array_merge($user_details, $file_string);
        }

        $tempfile = turnitintooltwo_tempfile($file_string, $filename);
        $fh = fopen($tempfile, "w");
        fwrite($fh, $textcontent);
        fclose($fh);

        // Create submission object.
        $submission = new TiiSubmission();
        $submission->setAssignmentId($tiiassignmentid);
        if ($apimethod == "replaceSubmission") {
            $submission->setSubmissionId($previoussubmission->externalid);
        }
        $submission->setTitle($title);
        $submission->setAuthorUserId($user->tii_user_id);

        if ($user->id == $USER->id && !is_siteadmin()) {
            $submission->setSubmitterUserId($user->tii_user_id);
            $submission->setRole('Learner');
        } else {
            $instructor = new turnitintooltwo_user($USER->id, 'Instructor');
            $instructor->edit_tii_user();

            $submission->setSubmitterUserId($instructor->tii_user_id);
            $submission->setRole('Instructor');
        }

        $submission->setSubmissionDataPath($tempfile);

        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $response = $turnitincall->$apimethod($submission);
            $newsubmission = $response->getSubmission();
            $newsubmissionid = $newsubmission->getSubmissionId();

            $plagiarismfile = new object();
            if ($apimethod == "replaceSubmission" || $submissionid != 0) {
                $plagiarismfile->id = $submissionid;
            }
            $plagiarismfile->cm = $cm->id;
            $plagiarismfile->userid = $user->id;
            $plagiarismfile->identifier = $identifier;
            $plagiarismfile->externalid = $newsubmissionid;
            $plagiarismfile->statuscode = 'success';
            $plagiarismfile->similarityscore = null;
            $plagiarismfile->attempt = 1;
            $plagiarismfile->transmatch = 0;
            $plagiarismfile->lastmodified = time();
            $plagiarismfile->submissiontype = $submissiontype;
            $plagiarismfile->errorcode = null;
            $plagiarismfile->errormsg = null;

            if ($apimethod == "replaceSubmission" || $submissionid != 0) {
                if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                    turnitintooltwo_activitylog("Update record failed (CM: ".$cm->id.", User: ".$user->id.")", "PP_UPDATE_SUB");
                }
            } else {
                if (!$fileid = $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
                    turnitintooltwo_activitylog("Insert record failed (CM: ".$cm->id.", User: ".$user->id.")", "PP_INSERT_SUB");
                }
            }

            // Add config field to show submissions have been made which we use to lock anonymous marking setting
            $configfield = new object();
            $configfield->cm = $cm->id;
            $configfield->name = 'submitted';
            $configfield->value = 1;

            if (!$currentconfigfield = $DB->get_field('plagiarism_turnitin_config', 'id',
                                                 (array('cm' => $cm->id, 'name' => 'submitted')))) {
                if (!$DB->insert_record('plagiarism_turnitin_config', $configfield)) {
                    turnitintooltwo_print_error('defaultupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                }
            }

            $return["success"] = true;
            $return["message"] = get_string('submissionuploadsuccess', 'turnitintooltwo').'<br/>'.
                                    get_string('turnitinsubmissionid', 'turnitintooltwo').': '.$newsubmissionid;

            if ($context == 'cron') {
                return true;
            }

        } catch (Exception $e) {
            $errorstring = (empty($previoussubmission->externalid)) ? "pp_createsubmissionerror" : "pp_updatesubmissionerror";

            $return["success"] = false;
            $return["message"] = get_string('pp_submission_error', 'turnitintooltwo').' '.$e->getMessage();

            $plagiarismfile = new object();
            if ($submissionid != 0) {
                $plagiarismfile->id = $submissionid;

                // Get attempt no
                $current_record = $DB->get_record('plagiarism_turnitin_files', array("id" => $submissionid));
                $plagiarismfile->attempt = $current_record->attempt + 1;
            } else {
                $plagiarismfile->attempt = 1;
            }
            $plagiarismfile->cm = $cm->id;
            $plagiarismfile->userid = $user->id;
            $plagiarismfile->identifier = $identifier;
            $plagiarismfile->statuscode = 'error';
            $plagiarismfile->lastmodified = time();
            $plagiarismfile->submissiontype = $submissiontype;
            $plagiarismfile->errorcode = 0;
            $plagiarismfile->errormsg = $return["message"];

            if ($submissionid != 0) {
                if (!$DB->update_record('plagiarism_turnitin_files', $plagiarismfile)) {
                    turnitintooltwo_activitylog("Update record failed (CM: ".$cm->id.", User: ".$user->id.") - ", "PP_UPDATE_SUB_ERROR");
                }
            } else {
                if (!$fileid = $DB->insert_record('plagiarism_turnitin_files', $plagiarismfile)) {
                    turnitintooltwo_activitylog("Insert record failed (CM: ".$cm->id.", User: ".$user->id.") - ", "PP_INSERT_SUB_ERROR");
                }
            }

            $turnitincomms->handle_exceptions($e, $errorstring, false);

            if ($context == 'cron') {
                mtrace('-------------------------');
                mtrace(get_string('pp_submission_error', 'turnitintooltwo').': '.$e->getMessage());
                mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
                mtrace('Course Module: '.$cm->id.'');
                mtrace('-------------------------');

                return false;
            }
        }

        return $return;
    }

    /**
     * Delete a submission from Turnitin
     */
    private function delete_tii_submission($submissionid) {

        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $submission = new TiiSubmission();
        $submission->setSubmissionId($submissionid);

        try {
            $response = $turnitincall->deleteSubmission($submission);
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'turnitindeletionerror');
        }
    }
}

/**
 * Submit file to Turnitin
 *
 * @param type $eventdata
 * @return type
 */
function plagiarism_turnitin_event_file_uploaded($eventdata) {
    $eventdata->event_type = 'file_uploaded';
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->event_handler($eventdata);
}

/**
 * Submit assesable content to Turnitin after student confirms their submission.
 *
 * This event is not fired by 2.3, as a workaround we submit everything to Turnitin.
 * In 2.4+ we don't submit the drafts, until submission is confirmed.
 *
 *
 * @param type $eventdata
 * @return boolean
 */
function plagiarism_turnitin_event_assessable_submitted($eventdata) {
    $eventdata->event_type = 'assessable_submitted';
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->event_handler($eventdata);
}

/**
 * Submit all files to Turnitin after student confirms their submission.
 *
 * This event is not fired by 2.3, as a workaround we submit everything to Turnitin.
 * In 2.4+ we don't submit the drafts, until submission is confirmed.
 *
 * @param type $eventdata
 * @return boolean
 */
function plagiarism_turnitin_event_files_done($eventdata) {
    $eventdata->event_type = 'files_done';
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->event_handler($eventdata);
}

/**
 * Create the module within Turnitin
 *
 * @param type $eventdata
 * @return boolean
 */
function plagiarism_turnitin_event_mod_created($eventdata) {
    $eventdata->event_type = 'mod_created';
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->event_handler($eventdata);
}

/**
 * Update the module within Turnitin
 *
 * @param type $eventdata
 * @return boolean
 */
function plagiarism_turnitin_event_mod_updated($eventdata) {
    $eventdata->event_type = 'mod_updated';
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->event_handler($eventdata);
}

/**
 * Remove submission data and config settins for module.
 *
 * @param type $eventdata
 * @return boolean true
 */
function plagiarism_turnitin_event_mod_deleted($eventdata) {
    global $DB;

    $DB->delete_records('plagiarism_turnitin_files', array('cm' => $eventdata->cmid));
    $DB->delete_records('plagiarism_turnitin_config', array('cm' => $eventdata->cmid));

    return true;
}

/**
 * Upload content to Turnitin
 *
 * @param type $eventdata
 * @return type
 */
function plagiarism_turnitin_event_content_uploaded($eventdata) {
    $eventdata->event_type = 'content_uploaded';
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->event_handler($eventdata);
}

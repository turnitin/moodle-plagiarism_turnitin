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
define('PLAGIARISM_TURNITIN_CRON_SUBMISSIONS_LIMIT', 100);

// Define accepted files if the module is not accepting any file type.
global $turnitinacceptedfiles;
$turnitinacceptedfiles = array('.doc', '.docx', '.ppt', '.pptx', '.pps', '.ppsx',
                                '.pdf', '.txt', '.htm', '.html', '.hwp', '.odt',
                                '.wpd', '.ps', '.rtf', '.xls', '.xlsx');

global $tiipp;
$tiipp = new stdClass();
$tiipp->in_use = true;

// Required classes from Moodle.
if ($CFG->branch < 28) {
    require_once($CFG->libdir.'/pluginlib.php');
}
require_once($CFG->libdir.'/gradelib.php');

// Get global class.
require_once($CFG->dirroot.'/plagiarism/lib.php');

// Require classes from mod/turnitintooltwo
require_once($CFG->dirroot.'/mod/turnitintooltwo/lib.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/turnitintooltwo_view.class.php');

// Include plugin classes
require_once(__DIR__."/turnitinplugin_view.class.php");
require_once(__DIR__.'/classes/turnitin_class.class.php');
require_once(__DIR__.'/classes/turnitin_submission.class.php');
require_once(__DIR__.'/classes/turnitin_comms.class.php');
require_once(__DIR__.'/classes/digitalreceipt/pp_receipt_message.php');

// Include supported module specific code
require_once(__DIR__.'/classes/modules/turnitin_assign.class.php');
require_once(__DIR__.'/classes/modules/turnitin_forum.class.php');
require_once(__DIR__.'/classes/modules/turnitin_workshop.class.php');
require_once(__DIR__.'/classes/modules/turnitin_coursework.class.php');


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
                        'plagiarism_erater_style', 'plagiarism_transmatch');
    }

    /**
     * Get the configuration settings for the plagiarism plugin
     *
     * @return mixed if plugin is enabled then an array of config settings is returned or false if not
     */
    public static function get_config_settings($modulename) {
        global $DB;
        $pluginconfig = get_config('plagiarism', 'turnitin_use_'.$modulename);

        return $pluginconfig;
    }

    /**
     * Get the Turnitin settings for a module
     *
     * @param int $cm_id - the course module id, if this is 0 the default settings will be retrieved
     * @param bool $uselockedvalues - use locked values in place of saved values
     * @return array of Turnitin settings for a module
     */
    public function get_settings($cmid = null, $uselockedvalues = true) {
        global $DB;
        $defaults = $DB->get_records_menu('plagiarism_turnitin_config', array('cm' => null),     '', 'name,value');
        $settings = $DB->get_records_menu('plagiarism_turnitin_config', array('cm' => $cmid), '', 'name,value');

        // Don't overwrite settings with locked values (only relevant on inital module creation).
        if ($uselockedvalues == false) {
            return $settings;
        }

        // Enforce site wide config locking.
        foreach ($defaults as $key => $value){
            if (substr($key, -5) !== '_lock'){
                continue;
            }
            if ($value != 1){
                continue;
            }
            $setting = substr($key, 0, -5);
            $settings[$setting] = $defaults[$setting];
        }

        return $settings;
    }

    /**
     * Get a list of the file upload errors.
     *
     * @param int $offset Number of records to skip.
     * @param int $limit  Max records to return.
     * @param bool $count If true, returns a count of the total number of
     *                    records.
     * @access public
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
        return $DB->get_records_sql($selectsql, array(), $offset, $limit);
    }

    /**
     * This function is called from the inbox in mod assign.
     * This plugin doesn't use it as it would impact negatively on the page loading.
     *
     * @param $course - Course the module is part of
     * @param $cm - Course module
     * @return string
     */
    public function update_status($course, $cm) {
        return '';
    }

    /**
     * Save the form data associated with the plugin
     *
     * @global type $DB
     * @param object $data the form data to save
     */
    public function save_form_elements($data) {
        global $DB;

        $moduletiienabled = $this->get_config_settings('mod_'.$data->modulename);
        if (empty($moduletiienabled)) {
            return;
        }

        $settingsfields = $this->get_settings_fields();
        // Get current values.
        $plagiarismvalues = $this->get_settings($data->coursemodule, false);

        foreach ($settingsfields as $field) {
            if (isset($data->$field)) {
                $optionfield = new stdClass();
                $optionfield->cm = $data->coursemodule;
                $optionfield->name = $field;
                $optionfield->value = $data->$field;

                if (isset($plagiarismvalues[$field])) {
                    $optionfield->id = $DB->get_field('plagiarism_turnitin_config', 'id',
                                                 (array('cm' => $data->coursemodule, 'name' => $field)));
                    if (!$DB->update_record('plagiarism_turnitin_config', $optionfield)) {
                        turnitintooltwo_print_error('defaultupdateerror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
                    }
                } else {
                    if (!$DB->insert_record('plagiarism_turnitin_config', $optionfield)) {
                        turnitintooltwo_print_error('defaultinserterror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
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
        global $DB, $COURSE;

        if (has_capability('plagiarism/turnitin:enable', $context)) {
            // Get Course module id and values.
            $cmid = optional_param('update', null, PARAM_INT);

            // Check Turnitin is configured.
            $config = turnitintooltwo_admin_config();
            if (empty($config->accountid) || empty($config->apiurl) || empty($config->secretkey)) {
                return;
            }

            // Check if plagiarism plugin is enabled for this module if provided.
            if (!empty($modulename)) {
                $moduletiienabled = $this->get_config_settings($modulename);
                if (empty($moduletiienabled)) {
                    return;
                }
            }

            // Get assignment settings, use default settings on assignment creation.
            $plagiarismvalues = $this->get_settings($cmid);

            /* If Turnitin is disabled and we don't have settings (we're editing an existing assignment that was created without Turnitin enabled)
             * Then we pass NULL for the $cmid to ensure we have the default settings should they enable Turnitin.
             */
            if (empty($plagiarismvalues["use_turnitin"]) && count($plagiarismvalues) <= 2) {
                $savedvalues = $plagiarismvalues;
                $plagiarismvalues = $this->get_settings(NULL);

                // Ensure we reuse the saved setting for use Turnitin.
                if (isset($savedvalues["use_turnitin"])) {
                    $plagiarismvalues["use_turnitin"] = $savedvalues["use_turnitin"];
                }
            }

            $plagiarismelements = $this->get_settings_fields();

            $turnitinpluginview = new turnitinplugin_view();
            $plagiarismvalues["plagiarism_rubric"] = ( !empty($plagiarismvalues["plagiarism_rubric"]) ) ?
                                                                $plagiarismvalues["plagiarism_rubric"] : 0;

            // Create/Edit course in Turnitin and join user to class.
            $course = $this->get_course_data($cmid, $COURSE->id);
            $turnitinpluginview->add_elements_to_settings_form($mform, $course, "activity", $cmid, $plagiarismvalues["plagiarism_rubric"]);

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

        // Get the modules that support the Plagiarism plugin by whether they have a class file.
        $supportedmods = array();
        foreach(scandir(__DIR__.'/classes/modules/') as $filename){
            if (!in_array($filename, array(".",".."))) {
                $filename_ar = explode('.', $filename);
                $classname_ar = explode('_', $filename_ar[0]); // $filename_ar[0] is class name.
                $supportedmods[] = $classname_ar[1]; // $classname_ar[1] is module name.
            }
        }

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
    public function test_turnitin_connection($workflowcontext = 'site') {
        $turnitincomms = new turnitin_comms();
        $tiiapi = $turnitincomms->initialise_api();

        $class = new TiiClass();
        $class->setTitle('Test finding a class to see if connection works');

        try {
            $response = $tiiapi->findClasses($class);
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

        $moduletiienabled = $this->get_config_settings('mod_'.$cm->modname);
        // Exit if Turnitin is not being used for this activity type.
        if (empty($moduletiienabled)) {
            return '';
        }

        $plagiarismsettings = $this->get_settings($cmid);
        // Check Turnitin is enabled for this current module.
        if (empty($plagiarismsettings['use_turnitin'])) {
            return '';
        }

        $this->load_page_components();

        // Show agreement.
        if (!empty($config->agreement)) {
            $contents = format_text($config->agreement, FORMAT_MOODLE, array("noclean" => true));
            $output = $OUTPUT->box($contents, 'generalbox boxaligncenter', 'intro');
        }

        // Show EULA if necessary and we have a connection to Turnitin.
        if ($tiiconnection) {
            $coursedata = $this->get_course_data($cm->id, $cm->course);

            $user = new turnitintooltwo_user($USER->id, "Learner");
            $user->join_user_to_class($coursedata->turnitin_cid);
            $eulaaccepted = ($user->user_agreement_accepted == 0) ? $user->get_accepted_user_agreement() : $user->user_agreement_accepted;

            if ($eulaaccepted != 1) {
                // Moodle strips out form and script code for forum posts so we have to do the Eula Launch differently.
                $ula_link = html_writer::link($CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$cmid.'&cmd=useragreement&view_context=box_solid',
                                        $OUTPUT->pix_icon('tiiIcon', '', 'plagiarism_turnitin', array('class' => 'icon_size_large')).'<br/>'.
                                        get_string('turnitinppulapre', 'plagiarism_turnitin'),
                                        array("class" => "pp_turnitin_eula_link"));

                $eulaignoredclass = ($eulaaccepted == 0) ? ' pp_turnitin_ula_ignored' : '';
                $ula = html_writer::tag('div', $ula_link, array('class' => 'pp_turnitin_ula js_required'.$eulaignoredclass,
                                            'data-userid' => $user->id));

                $noscriptula = html_writer::tag('noscript',
                                turnitintooltwo_view::output_dv_launch_form("useragreement", 0, $user->tii_user_id,
                                    "Learner", get_string('turnitinppulapre', 'plagiarism_turnitin'), false)." ".
                                        get_string('noscriptula', 'plagiarism_turnitin'),
                                            array('class' => 'warning turnitin_ula_noscript'));
            }

            // Show EULA launcher and form placeholder.
            if (!empty($ula)) {
                $output .= $ula.$noscriptula;

                $turnitincomms = new turnitin_comms();
                $turnitincall = $turnitincomms->initialise_api();

                $customdata = array("disable_form_change_checker" => true,
                                    "elements" => array(array('html', $OUTPUT->box('', '', 'useragreement_inputs'))));
                $eulaform = new turnitintooltwo_form($turnitincall->getApiBaseUrl().TiiLTI::EULAENDPOINT, $customdata,
                                                        'POST', $target = 'eulaWindow', array('id' => 'eula_launch'));
                $output .= $OUTPUT->box($eulaform->display(), 'tii_useragreement_form', 'useragreement_form');
            }
        }

        if ($config->usegrademark && !empty($plagiarismsettings["plagiarism_rubric"])) {

            // Update assignment in case rubric is not stored in Turnitin yet.
            $tiiassignment = $this->sync_tii_assignment($cm, $coursedata->turnitin_cid);

            $rubricviewlink = html_writer::tag('div', html_writer::link(
                                                    $CFG->wwwroot.'/plagiarism/turnitin/ajax.php?cmid='.$cm->id.
                                                                    '&action=rubricview&view_context=box',
                                                    get_string('launchrubricview', 'plagiarism_turnitin'),
                                                    array('class' => 'rubric_view_pp_launch', 'id' => 'rubric_view_launch',
                                                            'title' => get_string('launchrubricview', 'plagiarism_turnitin'))).
                                                                html_writer::tag('span', '',
                                                                array('class' => 'launch_form', 'id' => 'rubric_view_form')),
                                                    array('class' => 'row_rubric_view'));

            $output .= html_writer::tag('div', $rubricviewlink, array('class' => 'tii_links_container tii_disclosure_links'));
        }

        return $output;
    }

    /**
     * Load JS needed by the page.
     */
    public function load_page_components() {
        global $CFG, $PAGE;

        $jsurl = new moodle_url($CFG->wwwroot.'/plagiarism/turnitin/jquery/jquery-1.8.2.min.js');
        $PAGE->requires->js($jsurl);
        $jsurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/jquery/turnitintooltwo.js');
        $PAGE->requires->js($jsurl);
        $jsurl = new moodle_url($CFG->wwwroot.'/plagiarism/turnitin/jquery/turnitin_module.js');
        $PAGE->requires->js($jsurl);
        $jsurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/jquery/jquery.colorbox.js');
        $PAGE->requires->js($jsurl);
        $jsurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/jquery/jquery.tooltipster.js');
        $PAGE->requires->js($jsurl);

        // Include JS strings (closebutton is needed from both plugins).
        $PAGE->requires->string_for_js('closebutton', 'turnitintooltwo');
        $PAGE->requires->string_for_js('closebutton', 'plagiarism_turnitin');
        $PAGE->requires->string_for_js('loadingdv', 'plagiarism_turnitin');
    }

    /**
     * Get Moodle and Turnitin Course data
     */
    public function get_course_data($cmid, $courseid, $workflowcontext = 'site') {
        $coursedata = turnitintooltwo_assignment::get_course_data($courseid, 'PP', $workflowcontext);

        // get add from querystring to work out module type.
        $add = optional_param('add', '', PARAM_TEXT);

        if (empty($coursedata->turnitin_cid)) {
            // Course may have existed in a previous incarnation of this plugin.
            // Get this and save it in courses table if so.
            if ($turnitincid = $this->get_previous_course_id($cmid, $courseid)) {
                $coursedata->turnitin_cid = $turnitincid;
                $coursedata = $this->migrate_previous_course($coursedata, $turnitincid);
            } else {
                // Otherwise create new course in Turnitin if it doesn't exist.
                if ($cmid == 0) {
                    $tiicoursedata = $this->create_tii_course($cmid, $add, $coursedata, $workflowcontext);
                } else {
                    $cm = get_coursemodule_from_id('', $cmid);
                    $tiicoursedata = $this->create_tii_course($cmid, $cm->modname, $coursedata, $workflowcontext);
                }
                $coursedata->turnitin_cid = $tiicoursedata->turnitin_cid;
                $coursedata->turnitin_ctl = $tiicoursedata->turnitin_ctl;
            }
        }

        return $coursedata;
    }

    /**
     *
     * @global type $CFG
     * @param type $linkarray
     * @return type
     */
    public function get_links($linkarray) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        // Don't show links for certain file types as they won't have been submitted to Turnitin.
        if (!empty($linkarray["file"])) {
            $file = $linkarray["file"];
            $filearea = $file->get_filearea();
            $nonsubmittingareas = array("feedback_files", "introattachment");
            if (in_array($filearea, $nonsubmittingareas)) {
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
        }

        // Exit if Turnitin is not being used for this module.
        if (empty($plagiarismsettings['use_turnitin'])) {
            return;
        }

        static $config;
        if (empty($config)) {
            $config = turnitintooltwo_admin_config();
        }

        static $moduletiienabled;
        if (empty($moduletiienabled)) {
            $moduletiienabled = $this->get_config_settings('mod_'.$cm->modname);
        }

        // Exit if Turnitin is not being used for this activity type.
        if (empty($moduletiienabled)) {
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
            $coursedata = $this->get_course_data($cm->id, $cm->course);
        }

        // Create module object
        $moduleclass = "turnitin_".$cm->modname;
        $moduleobject = new $moduleclass;

        // Work out if logged in user is a tutor on this module.
        static $istutor;
        if (empty($istutor)) {
            $istutor = $moduleobject->is_tutor($context);
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

            $this->load_page_components();

            $identifier = '';
            $itemid = 0;

            // Get File or Content information.
            $submittinguser = $linkarray['userid'];
            if (!empty($linkarray["file"])) {
                $identifier = $file->get_pathnamehash();
                $itemid = $file->get_itemid();
                $submissiontype = 'file';
            } else if (!empty($linkarray["content"])) {
                // Get turnitin text content details.
                $submissiontype = ($cm->modname == "forum") ? 'forum_post' : 'text_content';
                $content = $moduleobject->set_content($linkarray, $cm);
                $identifier = sha1($content);
            }

            // Group submissions where all students have to submit sets userid to 0;
            if ($linkarray['userid'] == 0 && !$istutor) {
                $linkarray['userid'] = $USER->id;
            }

            // Get correct user id that submission is for rather than who submitted, this only affects file submissions
            // post Moodle 2.7 which is problematic as teachers can submit on behalf of students.
            $author = $linkarray['userid'];
            if ($itemid != 0) {
                $author = $moduleobject->get_author($itemid);
                $linkarray['userid'] = (!empty($author)) ? $author : $linkarray['userid'];
            }

            $output .= $OUTPUT->box_start('tii_links_container');

            // Show the EULA for a student if necessary.
            if ($linkarray["userid"] == $USER->id && empty($plagiarismfile->externalid)) {
                $eula = "";

                static $userid;
                if (empty($userid)) {
                    $userid = 0;
                }

                // Condition added to test for Moodle 2.7 as it calls this function twice.
                if ($CFG->branch >= 27 || $userid != $linkarray["userid"]) {
                    // Show EULA if necessary and we have a connection to Turnitin.
                    static $eulashown;
                    if (empty($eulashown)) {
                        $eulashown = false;
                    }

                    $user = new turnitintooltwo_user($USER->id, "Learner");
                    $success = $user->join_user_to_class($coursedata->turnitin_cid);

                    // $success is false if there is no Turnitin connection and null if user has previously been enrolled.
                    if ((is_null($success) || $success === true) && $eulashown == false) {
                        $eulaaccepted = ($user->user_agreement_accepted == 0) ? $user->get_accepted_user_agreement() : $user->user_agreement_accepted;
                        $userid = $linkarray["userid"];

                        if ($eulaaccepted != 1) {
                            $eula_link = html_writer::link($CFG->wwwroot.'/plagiarism/turnitin/extras.php?cmid='.$linkarray["cmid"].
                                    '&cmd=useragreement&view_context=box_solid',
                                    $OUTPUT->pix_icon('tiiIcon', '', 'plagiarism_turnitin', array('class' => 'icon_size_large')).'<br/>'.
                                    get_string('turnitinppulapost', 'plagiarism_turnitin'),
                                    array("class" => "pp_turnitin_eula_link"));

                            $eula = html_writer::tag('div', $eula_link, array('class' => 'pp_turnitin_ula js_required', 'data-userid' => $user->id));
                        }

                        // Show EULA launcher and form placeholder.
                        if (!empty($eula)) {
                            $output .= $eula;

                            $turnitincomms = new turnitin_comms();
                            $turnitincall = $turnitincomms->initialise_api();

                            $customdata = array("disable_form_change_checker" => true,
                                    "elements" => array(array('html', $OUTPUT->box('', '', 'useragreement_inputs'))));
                            $eulaform = new turnitintooltwo_form($turnitincall->getApiBaseUrl().TiiLTI::EULAENDPOINT, $customdata,
                                    'POST', $target = 'eulaWindow', array('id' => 'eula_launch'));
                            $output .= $OUTPUT->box($eulaform->display(), 'tii_useragreement_form', 'useragreement_form');
                            $eulashown = true;
                        }
                    }
                }
            }

            // Check whether submission is a group submission - only applicable to assignment module.
            // If it's a group submission then other users in the group should be able to see the originality score
            // They can not open the DV though.
            $submissionusers = array($linkarray["userid"]);
            if ($cm->modname == "assign") {
                if ($moduledata->teamsubmission) {
                    $assignment = new assign($context, $cm, null);
                    if ($group = $assignment->get_submission_group($linkarray["userid"])) {
                        $users = groups_get_members($group->id);
                        $submissionusers = array_keys($users);
                    }
                }
            }


            // Group originality score for Coursework module
            // Check whether submission is a group submission
            // If it's a group submission then other users in the group should be able to see the originality score
            // They can not open the DV though.
            if ($cm->modname == "coursework") {
                if ($moduledata->use_groups) {

                    $coursework = new \mod_coursework\models\coursework($moduledata->id);

                    $user = $DB->get_record('user', array('id' => $linkarray["userid"]));
                    $user = mod_coursework\models\user::find($user);
                    if ($group = $coursework->get_student_group($user)) {
                        $users = groups_get_members($group->id);
                        $submissionusers = array_keys($users);
                    }
                }
            }




            // Proceed to displaying links for submissions.
            if ($istutor || in_array($USER->id, $submissionusers)) {

                // Prevent text content links being displayed for previous attempts as we have no way of getting the data.
                if (!empty($linkarray["content"]) && $linkarray["userid"] == $USER->id) {
                    $contentdisplayed = true;
                }

                // Get turnitin file details
                $plagiarismfiles = $DB->get_records('plagiarism_turnitin_files', array('userid' => $linkarray["userid"],
                                                        'cm' => $linkarray["cmid"], 'identifier' => $identifier),
                                                        'lastmodified DESC', '*', 0, 1);
                $plagiarismfile = current($plagiarismfiles);

                // Populate gradeitem query
                $gradeitemqueryarray = array(
                                    'iteminstance' => $cm->instance,
                                    'itemmodule' => $cm->modname,
                                    'courseid' => $cm->course,
                                    'itemnumber' => 0
                                );

                // Get grade item and work out whether grades have been released for viewing.
                $gradesreleased = true;
                if ($gradeitem = $DB->get_record('grade_items', $gradeitemqueryarray)) {
                    switch ($gradeitem->hidden) {
                        case 1:
                            $gradesreleased = false;
                            break;
                        default:
                            $gradesreleased = ($gradeitem->hidden >= time()) ? false : true;
                            break;
                    }

                    // Give Marking workflow higher priority than gradebook hidden date.
                    if ($cm->modname == 'assign' && !empty($moduledata->markingworkflow)) {
                        $gradesreleased = $DB->record_exists(
                                                    'assign_user_flags',
                                                    array(
                                                        'userid' => $linkarray["userid"],
                                                        'assignment' => $cm->instance,
                                                        'workflowstate' => 'released'
                                                    ));
                    }
                }

                $currentgradequery = false;
                if ($gradeitem) {
                    $currentgradequery = $moduleobject->get_current_gradequery($linkarray["userid"], $cm->instance, $gradeitem->id);
                }

                // Display links to OR, GradeMark and show relevant errors.
                if ($plagiarismfile) {

                    if ($plagiarismfile->statuscode == 'success') {
                        if ($istutor || $linkarray["userid"] == $USER->id) {
                            $output .= html_writer::tag('div',
                                            $OUTPUT->pix_icon('tiiIcon',
                                                get_string('turnitinid', 'plagiarism_turnitin').': '.$plagiarismfile->externalid, 'plagiarism_turnitin', array('class' => 'icon_size')).
                                                get_string('turnitinid', 'plagiarism_turnitin').': '.$plagiarismfile->externalid,
                                            array('class' => 'turnitin_status'));
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
                                $output .= html_writer::tag('div', '&nbsp;', array('title' => get_string('pending', 'plagiarism_turnitin'),
                                                                        'class' => 'tii_tooltip origreport_score score_colour score_colour_'));
                            } else {
                                // Put EN flag if translated matching is on and that is the score used.
                                $transmatch = ($plagiarismfile->transmatch == 1) ? ' EN' : '';

                                if (is_null($plagiarismfile->similarityscore)) {
                                    $score = '&nbsp;';
                                    $titlescore = get_string('pending', 'plagiarism_turnitin');
                                    $class = 'score_colour_';
                                } else {
                                    $score = $plagiarismfile->similarityscore.'%';
                                    $titlescore = $plagiarismfile->similarityscore.'% '.get_string('similarity', 'plagiarism_turnitin');
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
                            $output .= html_writer::tag('div', 'x', array('title' => get_string('notorcapable', 'plagiarism_turnitin'),
                                                                        'class' => 'tii_tooltip score_colour score_colour_ score_no_orcapable'));
                            $output .= $OUTPUT->box_end(true);
                        }

                        //Check if blind marking is on and revealidentities is not set yet.
                        $blindon = (!empty($moduledata->blindmarking) && empty($moduledata->revealidentities));

                        // Can grade and feedback be released to this student yet?
                        $released = ((!$blindon) && ($gradesreleased && (!empty($plagiarismfile->gm_feedback) || isset($currentgradequery->grade))));

                        // Show link to open grademark.
                        if ($config->usegrademark && ($istutor || ($linkarray["userid"] == $USER->id && $released))
                                 && !empty($gradeitem)) {

                            // Output grademark icon.
                            $output .= $OUTPUT->box_start('grade_icon', '');
                            $output .= html_writer::tag('div', $OUTPUT->pix_icon('icon-edit',
                                                                get_string('grademark', 'plagiarism_turnitin'), 'plagiarism_turnitin'),
                                                    array('title' => get_string('grademark', 'plagiarism_turnitin'),
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

                        // Indicate whether student has viewed the feedback.
                        if ($istutor) {
                            if (isset($plagiarismfile->externalid)) {
                                $studentread = (!empty($plagiarismfile->student_read)) ? $plagiarismfile->student_read : 0;
                                if ($studentread > 0) {
                                    $output .= $OUTPUT->pix_icon('icon-student-read',
                                                        get_string('student_read', 'plagiarism_turnitin').' '.userdate($studentread),
                                                        'plagiarism_turnitin', array("class" => "student_read_icon"));
                                } else {
                                    $output .= $OUTPUT->pix_icon('icon-dot', get_string('student_notread', 'plagiarism_turnitin'),
                                                        'plagiarism_turnitin', array("class" => "student_read_icon"));
                                }
                            } else {
                                $output .= "--";
                            }
                        }

                        // Show link to view rubric for student.
                        if (!$istutor && $config->usegrademark && !empty($plagiarismsettings["plagiarism_rubric"])) {
                            // Update assignment in case rubric is not stored in Turnitin yet.
                            $tiiassignment = $this->sync_tii_assignment($cm, $coursedata->turnitin_cid);

                            $rubricviewlink = html_writer::tag('div', html_writer::link(
                                                            $CFG->wwwroot.'/plagiarism/turnitin/ajax.php?cmid='.$cm->id.
                                                                    '&action=rubricview&view_context=box', '&nbsp;',
                                                            array('class' => 'tii_tooltip rubric_view_pp_launch', 'id' => 'rubric_view_launch',
                                                                    'title' => get_string('launchrubricview', 'plagiarism_turnitin'))).
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
                                                                array('title' => get_string('launchpeermarkreviews', 'plagiarism_turnitin'),
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
                        $errorcode = (isset($plagiarismfile->errorcode)) ? $plagiarismfile->errorcode : 0;
                        if ($errorcode == 0 && $submissiontype == 'file') {
                            if ($file->get_filesize() > TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE) {
                                $errorcode = 2;
                            }
                        }

                        // Show error message if there is one.
                        if ($errorcode == 0) {
                            $langstring = ($istutor) ? 'ppsubmissionerrorseelogs' : 'ppsubmissionerrorstudent';
                            $errorstring = (isset($plagiarismfile->errormsg)) ?
                                                get_string($langstring, 'plagiarism_turnitin') : $plagiarismfile->errormsg;
                        } else {
                            $errorstring = get_string('errorcode'.$plagiarismfile->errorcode,
                                            'plagiarism_turnitin', display_size(TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE));
                        }

                        $erroricon = html_writer::tag('div', $OUTPUT->pix_icon('x-red', $errorstring, 'plagiarism_turnitin'),
                                                                array('title' => $errorstring,
                                                                        'class' => 'tii_tooltip tii_error_icon'));

                        // Attach error text or resubmit link after icon depending on whether user is a student/teacher.
                        // Don't attach resubmit link if the user has not accepted the EULA.
                        if (!$istutor) {
                            $output .= html_writer::tag('div', $erroricon.' '.$errorstring, array('class' => 'warning clear'));
                        } else if ($errorcode == 3) {
                            $output .= html_writer::tag('div', $erroricon, array('class' => 'clear'));
                        } else {
                            $output .= html_writer::tag('div', $erroricon.' '.get_string('resubmittoturnitin', 'plagiarism_turnitin'),
                                                        array('class' => 'clear pp_resubmit_link',
                                                                'id' => 'pp_resubmit_'.$plagiarismfile->id));

                            $output .= html_writer::tag('div',
                                                        $OUTPUT->pix_icon('loading', $errorstring, 'plagiarism_turnitin').' '.
                                                        get_string('resubmitting', 'plagiarism_turnitin'),
                                                        array('class' => 'pp_resubmitting hidden'));

                            // Pending status for after resubmission.
                            $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin').': '.get_string('pending', 'plagiarism_turnitin');
                            $output .= html_writer::tag('div', $OUTPUT->pix_icon('tiiIcon', $statusstr, 'plagiarism_turnitin', array('class' => 'icon_size')).$statusstr,
                                                        array('class' => 'turnitin_status hidden'));

                            // Show hidden data for potential forum post resubmissions
                            if ($submissiontype == 'forum_post' && !empty($linkarray["content"])) {
                                $output .= html_writer::tag('div', $linkarray["content"],
                                                            array('class' => 'hidden', 'id' => 'content_'.$plagiarismfile->id));
                            }

                            if ($cm->modname == 'forum') {
                                // Get forum data from the query string as we'll need this to recreate submission event.
                                $querystrid = optional_param('id', 0, PARAM_INT);
                                $discussionid = optional_param('d', 0, PARAM_INT);
                                $reply   = optional_param('reply', 0, PARAM_INT);
                                $edit    = optional_param('edit', 0, PARAM_INT);
                                $delete  = optional_param('delete', 0, PARAM_INT);
                                $output .= html_writer::tag('div', $querystrid.'_'.$discussionid.'_'.$reply.'_'.$edit.'_'.$delete,
                                                            array('class' => 'hidden', 'id' => 'forumdata_'.$plagiarismfile->id));
                            }
                        }
                    } else if ($plagiarismfile->statuscode == 'deleted'){
                        $errorcode = (isset($plagiarismfile->errorcode)) ? $plagiarismfile->errorcode : 0;
                        if ($errorcode == 0) {
                            $langstring = ($istutor) ? 'ppsubmissionerrorseelogs' : 'ppsubmissionerrorstudent';
                            $errorstring = (isset($plagiarismfile->errormsg)) ?
                                                get_string($langstring, 'plagiarism_turnitin') : $plagiarismfile->errormsg;
                        } else {
                            $errorstring = get_string('errorcode'.$plagiarismfile->errorcode,
                                            'plagiarism_turnitin', display_size(TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE));
                        }
                        $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin').': '.get_string('deleted', 'plagiarism_turnitin').'<br />';
                        $statusstr .= get_string('because', 'plagiarism_turnitin').'<br />"'.$errorstring.'"';
                        $output .= html_writer::tag('div', $OUTPUT->pix_icon('tiiIcon', $statusstr, 'plagiarism_turnitin', array('class' => 'icon_size')).$statusstr,
                            array('class' => 'turnitin_status'));

                    } else {
                        $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin').': '.get_string('pending', 'plagiarism_turnitin');
                        $output .= html_writer::tag('div', $OUTPUT->pix_icon('tiiIcon', $statusstr, 'plagiarism_turnitin', array('class' => 'icon_size')).$statusstr,
                                                    array('class' => 'turnitin_status'));
                    }

                }
                else {
                    // Add Error if the user has not accepted EULA for submissions made before instant submission was removed.
                    $eulaerror = "";
                    if ($linkarray["userid"] != $USER->id && $submittinguser == $author && $istutor) {
                        // There is a moodle plagiarism bug where get_links is called twice, the first loop is incorrect and is killing
                        // this functionality. Have to check that user exists here first else there will be a fatal error.
                        if ($mdl_user = $DB->get_record('user', array('id' => $linkarray["userid"]))) {
                            // We need to check for security that the user is actually on the course.
                            if ($moduleobject->user_enrolled_on_course($context, $linkarray["userid"])) {
                                $user = new turnitintooltwo_user($linkarray["userid"], "Learner");
                                if ($user->user_agreement_accepted != 1) {
                                    $erroricon = html_writer::tag('div', $OUTPUT->pix_icon('doc-x-grey', get_string('errorcode3', 'plagiarism_turnitin'),
                                                                            'plagiarism_turnitin'),
                                                                            array('title' => get_string('errorcode3', 'plagiarism_turnitin'),
                                                                                    'class' => 'tii_tooltip tii_error_icon'));
                                    $eulaerror = html_writer::tag('div', $erroricon, array('class' => 'clear'));
                                }
                            }
                        }
                    }

                    // Show Turnitin Pending status or EULA error.
                    if (!empty($eulaerror)) {
                        $output .= $eulaerror;
                    } else {
                        $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin').': '.get_string('pending', 'plagiarism_turnitin');
                        $output .= html_writer::tag('div', $OUTPUT->pix_icon('tiiIcon', $statusstr, 'plagiarism_turnitin', array('class' => 'icon_size')).$statusstr,
                                                    array('class' => 'turnitin_status'));
                    }
                }

                $output .= html_writer::tag('div', '', array('class' => 'clear'));
            }

            $output .= $OUTPUT->box_end(true);
        }

        // This comment is here as it is useful for product support.
        $plagiarismsettings = $this->get_settings($cm->id);
        $turnitinassignid = (empty($plagiarismsettings['turnitin_assignid'])) ? '' : $plagiarismsettings['turnitin_assignid'];
        $output .= html_writer::tag(
            'span', '<!-- Turnitin Plagiarism plugin Version: '.get_config('plagiarism_turnitin', 'version').' Course ID: '.$coursedata->turnitin_cid.' TII assignment ID: '.$turnitinassignid.' -->');

        return $output;
    }

    public function update_grades_from_tii($cm) {
        global $DB;
        $plagiarismvalues = $this->get_settings($cm->id);
        $submissionids = array();
        $return = true;

        // Initialise Comms Object.
        $turnitincomms = new turnitin_comms();
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
        $turnitincomms = new turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $submission = new TiiSubmission();
            $submission->setSubmissionId($submissionid);

            $response = $turnitincall->readSubmission($submission);

            $readsubmission = $response->getSubmission();

            $submissiondata = $DB->get_record('plagiarism_turnitin_files',
                                                array('externalid' => $readsubmission->getSubmissionId()), 'id');

            $this->update_submission($cm, $submissiondata->id, $readsubmission);

        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
            $return = false;
        }

        return $return;
    }

    private function update_submission($cm, $submissionid, $tiisubmission) {
        global $DB, $CFG;

        $return = true;
        $updaterequired = false;

        if ($submissiondata = $DB->get_record('plagiarism_turnitin_files', array('id' => $submissionid),
                                                 'id, cm, userid, identifier, similarityscore, grade, submissiontype, orcapable, student_read, gm_feedback')) {
            $plagiarismfile = new stdClass();
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

            $plagiarismfile->gm_feedback = $tiisubmission->getFeedbackExists();

            //Update feedback timestamp.
            $plagiarismfile->student_read = ($tiisubmission->getAuthorLastViewedFeedback() > 0) ?
                                    strtotime($tiisubmission->getAuthorLastViewedFeedback()) : 0;

            // Identify if an update is required for the similarity score and grade.
            if (!is_null($plagiarismfile->similarityscore) || !is_null($plagiarismfile->grade) ||
                    !is_null($plagiarismfile->orcapable)) {
                if ($submissiondata->similarityscore != $plagiarismfile->similarityscore ||
                        $submissiondata->grade != $plagiarismfile->grade ||
                        $submissiondata->orcapable != $plagiarismfile->orcapable ||
                        $submissiondata->student_read != $plagiarismfile->student_read ||
                        $submissiondata->gm_feedback != $plagiarismfile->gm_feedback) {
                    $updaterequired = true;
                }
            }
            // Don't update grademark if the submission is not part of the latest attempt.
            $gbupdaterequired = $updaterequired;
            if ($cm->modname == "assign") {
                if ($submissiondata->submissiontype == "file") {
                    $fs = get_file_storage();
                    if ($file = $fs->get_file_by_hash($submissiondata->identifier)) {
                        $itemid = $file->get_itemid();

                        $submission = $DB->get_records('assign_submission', array('assignment' => $cm->instance, 'userid' => $submissiondata->userid), 'id DESC', 'id, attemptnumber', '0', '1');
                        $item = current($submission);

                        if ($item->id != $itemid) {
                             $gbupdaterequired = false;
                        }
                    } else {
                        $gbupdaterequired = false;
                    }
                } elseif ($submissiondata->submissiontype == "text_content") {
                    // Get latest submission
                    $moduleobject = new turnitin_assign();
                    $latesttext = $moduleobject->get_onlinetext($submissiondata->userid, $cm);
                    $latestidentifier = sha1($latesttext->onlinetext);
                    // Check submission being graded is latest.
                    if ($submissiondata->identifier != $latestidentifier) {
                        $gbupdaterequired = false;
                    }
                }
            }

            // Only update as necessary.
            if ($updaterequired) {
                $DB->update_record('plagiarism_turnitin_files', $plagiarismfile);

                if ($cm->modname == "coursework") {
                    // at the moment TII doesn't support double marking so we won't synchronise grades from Grade Mark as it would destroy the workflow
                    return true;
                }
                $gradeitem = $DB->get_record('grade_items',
                                    array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname,
                                            'courseid' => $cm->course, 'itemnumber' => 0));

                if (!is_null($plagiarismfile->grade) && !empty($gradeitem) && $gbupdaterequired) {
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

            // Create module object.
            $moduleclass = "turnitin_".$cm->modname;
            $moduleobject = new $moduleclass;

            // Get file from pathname hash
            $submissiondata = $DB->get_record('plagiarism_turnitin_files', array('externalid' => $submission->getSubmissionId()), 'identifier');

            // Get file as we need item id for discounting files that are no longer in submission.
            $fs = get_file_storage();
            if ($file = $fs->get_file_by_hash($submissiondata->identifier)) {
                $moodlefiles = $DB->get_records_select('files', " component = ? AND itemid = ? AND source IS NOT null ",
                                                    array($moduleobject->filecomponent, $file->get_itemid()), 'id DESC', 'pathnamehash');

                list($insql, $inparams) = $DB->get_in_or_equal(array_keys($moodlefiles), SQL_PARAMS_QM, 'param', true);
                $tiisubmissions = $DB->get_records_select('plagiarism_turnitin_files', " userid = ? AND cm = ? AND identifier ".$insql,
                                                        array_merge(array($userid, $cm->id), $inparams));
            } else {
                $tiisubmissions = $DB->get_records('plagiarism_turnitin_files', array('userid' => $userid, 'cm' => $cm->id));
                $tiisubmissions = current($tiisubmissions);
            }

            if (count($tiisubmissions) > 1) {
                $averagegrade = null;
                $gradescounted = 0;
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
            $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
            if ($cm->modname == "assign" && !empty($moduledata->teamsubmission)) {
                require_once($CFG->dirroot . '/mod/assign/locallib.php');
                $context = context_course::instance($cm->course);
                $assignment = new assign($context, $cm, null);

                if ($group = $assignment->get_submission_group($userid)) {
                    $users = groups_get_members($group->id);
                    $userids = array_keys($users);
                }
            }

            // Loop through all users and update grade
            foreach ($userids as $userid) {
                // Get gradebook data.
                switch ($cm->modname) {
                    case 'assign':

                        // Query grades based on attempt number.
                        $gradesquery = array('userid' => $userid, 'assignment' => $cm->instance);
                        $attemptnumber = 0;

                        $usersubmissions = $DB->get_records('assign_submission', $gradesquery, 'attemptnumber DESC', 'attemptnumber', 0, 1);
                        $usersubmission = current($usersubmissions);
                        $attemptnumber = $usersubmission->attemptnumber;
                        $gradesquery['attemptnumber'] = $attemptnumber;

                        $currentgrades = $DB->get_records('assign_grades', $gradesquery, 'id DESC');
                        $currentgrade = current($currentgrades);
                        break;
                    case 'workshop':
                        if ($gradeitem = $DB->get_record('grade_items', array('iteminstance' => $cm->instance,
                                                        'itemmodule' => $cm->modname, 'itemnumber' => 0))) {
                            $currentgrade = $DB->get_record('grade_grades', array('userid' => $userid, 'itemid' => $gradeitem->id));
                        }
                        break;
                }

                // Create module object.
                $moduleclass = "turnitin_".$cm->modname;
                $moduleobject = new $moduleclass;

                // Configure grade object and save to db.
                $table = $moduleobject->grades_table;
                $grade->timemodified = time();

                if ($currentgrade) {
                    $grade->id = $currentgrade->id;
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
                        $gradesreleased = $DB->record_exists('assign_user_flags',
                                                                array(
                                                                    'userid' => $userid,
                                                                    'assignment' => $cm->instance,
                                                                    'workflowstate' => 'released'
                                                                    ));
                        // Remove any existing grade from gradebook if not released.
                        if (!$gradesreleased) {
                            $grades->rawgrade = null;
                        }
                    }

                    $params['idnumber'] = $cm->idnumber;

                    // Update gradebook - Grade update returns 1 on failure and 0 if successful.
                    $gradeupdate = $cm->modname."_grade_item_update";
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
     */
    public function create_tii_course($cmid, $modname, $coursedata, $workflowcontext = "site") {
        global $CFG, $USER;

        // Create module object.
        $moduleclass = "turnitin_".$modname;
        $moduleobject = new $moduleclass;

        $capability = $moduleobject->get_tutor_capability();
        if (!empty($cmid)) {
            $tutors = get_enrolled_users(context_module::instance($cmid), $capability, 0, 'u.id', 'u.id');
        } else {
            $tutors = get_enrolled_users(context_course::instance($coursedata->id), $capability, 0, 'u.id', 'u.id');
        }

        $ownerid = $USER->id;

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
        $turnitincomms = new turnitin_comms();
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
    public function sync_tii_assignment($cm, $coursetiiid, $workflowcontext = "site", $submittoturnitin = false) {
        global $DB, $CFG;

        $config = turnitintooltwo_admin_config();
        $modulepluginsettings = $this->get_settings($cm->id);
        $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));

        // Configure assignment object to send to Turnitin.
        $assignment = new TiiAssignment();
        $assignment->setClassId($coursetiiid);

        // We need to truncate the moodle assignment title to be compatible with a Turnitin
        // assignment title (max length 99) and account for non English multibyte strings.
        $title = "";
        if ( mb_strlen( $moduledata->name, 'UTF-8' ) > 80 ) {
            $title .= mb_substr( $moduledata->name, 0, 80, 'UTF-8' ) . "...";
        } else {
            $title .= $moduledata->name;
        }
        $title .= " (Moodle PP)";
        $assignment->setTitle( $title );

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
        $assignment->setResubmissionRule((int)$modulepluginsettings["plagiarism_report_gen"]);
        $assignment->setBibliographyExcluded($modulepluginsettings["plagiarism_exclude_biblio"]);
        $assignment->setQuotedExcluded($modulepluginsettings["plagiarism_exclude_quoted"]);
        $assignment->setSmallMatchExclusionType($modulepluginsettings["plagiarism_exclude_matches"]);
        $modulepluginsettings["plagiarism_exclude_matches_value"] =
                        (!empty($modulepluginsettings["plagiarism_exclude_matches_value"])) ?
                                $modulepluginsettings["plagiarism_exclude_matches_value"] : 0;

        $assignment->setSmallMatchExclusionThreshold($modulepluginsettings["plagiarism_exclude_matches_value"]);

        // Don't set anonymous marking if there have been submissions.
        $previoussubmissions = $DB->record_exists('plagiarism_turnitin_files',
                                                            array('cm' => $cm->id, 'statuscode' => 'success'));

        // Use Moodle's blind marking setting for anonymous marking.
        if ($config->useanon && !$previoussubmissions) {
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

        if (!empty($moduledata->allowsubmissionsfromdate)) {
            $dtstart = $moduledata->allowsubmissionsfromdate;
        } else if (!empty($moduledata->timeavailable)) {
            $dtstart = $moduledata->timeavailable;
        } else {
            $dtstart = $cm->added;
        }
        $dtstart = ($dtstart <= strtotime('-1 year')) ? strtotime('-11 months') : $dtstart;
        $assignment->setStartDate(gmdate("Y-m-d\TH:i:s\Z", $dtstart));

        // Set post date. If "hidden until" has been set in gradebook then we will use that value, otherwise we will
        // use start date. If the grades are to be completely hidden then we will set post date in the future.
        // From 2.6, if grading markflow is enabled and no grades have been released, we will use due date +4 weeks.
        $dtpost = 0;
        if ($cm->modname != "forum") {
            if ($gradeitem = $DB->get_record(
                                            'grade_items',
                                            array(
                                                'iteminstance' => $cm->instance,
                                                'itemmodule' => $cm->modname,
                                                'courseid' => $cm->course,
                                                'itemnumber' => 0)
                                            )) {

                switch ($gradeitem->hidden) {
                    case 1:
                        $dtpost = strtotime('+6 months');
                        break;
                    case 0:
                        $dtpost = $dtstart;
                        // If any grades have been released early via marking workflow, set post date to current time.
                        if ($cm->modname == 'assign' && !empty($moduledata->markingworkflow)) {
                            $gradesreleased = $DB->record_exists('assign_user_flags',
                                                            array('assignment' => $cm->instance,
                                                                    'workflowstate' => 'released'));

                            $dtpost = ($gradesreleased) ? time() : strtotime('+1 month');
                        }
                        break;
                    default:
                        $dtpost = $gradeitem->hidden;
                        break;
                }
            }
        }

        // If blind marking is being used and identities have not been revealed then push out post date.
        if ($cm->modname == 'assign' && !empty($moduledata->blindmarking) && empty($moduledata->revealidentities)) {
            $dtpost = strtotime('+6 months');
        }

        // If blind marking is being used for coursework then push out post date.
        if ($cm->modname == 'coursework' && !empty($moduledata->blindmarking)) {
            $dtpost = strtotime('+6 months');
        }

        // Ensure post date can't be before start date
        if ($dtpost < $dtstart) {
            $dtpost = $dtstart;
        }

        // Set due date, dependent on various things.
        $dtdue = (!empty($moduledata->duedate)) ? $moduledata->duedate : 0;

        // If the due date has been set more than a year ahead then restrict it to 1 year from now.
        if ($dtdue > strtotime('+1 year')) {
            $dtdue = strtotime('+1 year');
        }

        // Ensure due date can't be before start date
        if ($dtdue <= $dtstart) {
            $dtdue = strtotime('+1 month', $dtstart);
        }

        // Ensure due date is always in the future for submissions.
        if ($dtdue <= time() && $submittoturnitin) {
            $dtdue = strtotime('+1 day');
        }

        $assignment->setDueDate(gmdate("Y-m-d\TH:i:s\Z", $dtdue));

        // If the duedate is in the future then set any submission duedate_report_refresh flags that are 2 to 1 to make sure they are re-examined in the next cron run
        if ($dtdue > time()) {
            $DB->set_field('plagiarism_turnitin_files', 'duedate_report_refresh', 1, array('cm' => $cm->id, 'duedate_report_refresh' => 2));
        }

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

            $return = $turnitintooltwoassignment->edit_tii_assignment($assignment, $workflowcontext);
            $return['errorcode'] = ($return['success']) ? 0 : 6;

            return $return;
        } else {
            $turnitintooltwoassignment = new turnitintooltwo_assignment(0, '', 'PP');
            $turnitinassignid = $turnitintooltwoassignment->create_tii_assignment($assignment, 0, 0, 'plagiarism_plugin', $workflowcontext);

            if (!$turnitinassignid) {
                $return = array('success' => false, 'tiiassignmentid' => '', 'errorcode' => 5);
            } else {
                $moduleconfigvalue = new stdClass();
                $moduleconfigvalue->cm = $cm->id;
                $moduleconfigvalue->name = 'turnitin_assignid';
                $moduleconfigvalue->value = $turnitinassignid;
                $DB->insert_record('plagiarism_turnitin_config', $moduleconfigvalue);

                $return = array('success' => true, 'tiiassignmentid' => $assignment->getAssignmentId());
            }

            return $return;
        }
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
        global $DB, $CFG, $PLAGIARISM_TURNITIN_TASKCALL;

        // 2.7 onwards we would like to be called from task calls.
        if ( $CFG->version > 2014051200 AND !$PLAGIARISM_TURNITIN_TASKCALL ){
            mtrace("[Turnitin Plagiarism Plugin] Aborted Cron call because of active task mode");
            return;
        }

        // Reset task call flag.
        if ( $PLAGIARISM_TURNITIN_TASKCALL ) {
            $PLAGIARISM_TURNITIN_TASKCALL = false;
        }

        // Update scores by separate submission type.
        $submissiontypes = array('file', 'text_content', 'forum_post');
        foreach ($submissiontypes as $submissiontype) {
            try {
                $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";
                $submissions = $DB->get_records_select('plagiarism_turnitin_files',
                " statuscode = ? AND ".$typefield." = ?
                  AND ( similarityscore IS NULL OR duedate_report_refresh = 1 )
                  AND ( orcapable = ? OR orcapable IS NULL ) ",
                array('success', $submissiontype, 1), 'externalid DESC');
                $this->cron_update_scores($submissiontype, $submissions);
            } catch (Exception $ex) {
                error_log("Exception in TII cron while updating scores for '$submissiontype' submission types: ".$ex);
                mtrace("Exception in TII cron while updating scores for '$submissiontype' submission types: ".$ex);
            }
        }
        return true;
    }

    /**
    * Updates the database field duedate_report_refresh for any given submission ID.
    * @param int $id - the ID of the submission to update.
    * @param int $newValue - the value to which the field should be set.
    */
    public function set_duedate_report_refresh($id, $newValue) {
        global $DB;

        $update_data = new stdClass();
        $update_data->id = $id;
        $update_data->duedate_report_refresh = $newValue;
        $DB->update_record('plagiarism_turnitin_files', $update_data);
    }

    /**
     * Update simliarity scores.
     * @param array $submissions - the submissions to be processed
     * @return boolean
     */
    public function cron_update_scores($submissiontype = 'file', $submissions) {
        global $DB, $CFG;

        $submissionids = array();
        $reportsexpected = array();

        // Add submission ids to the request.
        foreach ($submissions as $tiisubmission) {

            // Only add the submission to the request if the module still exists.
            if ($cm = get_coursemodule_from_id('', $tiisubmission->cm)) {

                // Updates the db field 'duedate_report_refresh' if the due date has passed within the last twenty four hours.
                $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
                $now = strtotime('now');
                $dtdue = (!empty($moduledata->duedate)) ? $moduledata->duedate : 0;
                if ($now >= $dtdue && $now < strtotime('+1 day',$dtdue)) {
                    $this->set_duedate_report_refresh($tiisubmission->id, 1);
                }

                if (!isset($reportsexpected[$cm->id])) {
                    $plagiarismsettings = $this->get_settings($cm->id);
                    $reportsexpected[$cm->id] = 1;

                    if (!isset($plagiarismsettings['plagiarism_compare_institution'])) {
                        $plagiarismsettings['plagiarism_compare_institution'] = 0;
                    }

                    // Don't add the submission to the request if module settings mean we will not get a report back.
                    if (array_key_exists('plagiarism_compare_student_papers', $plagiarismsettings) &&
                        $plagiarismsettings['plagiarism_compare_student_papers'] == 0 &&
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
                $turnitincomms = new turnitin_comms();
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

                            $currentsubmission = $DB->get_record('plagiarism_turnitin_files', array('externalid' => $tiisubmissionid), 'id, cm, externalid, userid');
                            if ($cm = get_coursemodule_from_id('', $currentsubmission->cm)) {

                                $plagiarismfile = new stdClass();
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
                    mtrace(get_string('tiisubmissionsgeterror', 'plagiarism_turnitin'));
                    $turnitincomms->handle_exceptions($e, 'tiisubmissionsgeterror', false);
                    // Do not return false if a batch fails - another one might work.
                }
            }
        }

        // Sets the duedate_report_refresh flag for each processed submission to 2 to prevent them being processed again in the next cron run
        foreach ($submissions as $tiisubmission) {
            if ($cm = get_coursemodule_from_id('', $tiisubmission->cm)) {
                $this->set_duedate_report_refresh($tiisubmission->id, 2);
            }
        }

        return true;
    }

    /**
     * Get a class Id from Turnitin if you only have an assignment id.
     */
    private function get_course_id_from_assignment_id($assignmentid) {
        // Initialise Comms Object.
        $turnitincomms = new turnitin_comms();
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
    public function get_previous_course_id($cmid, $courseid) {
        global $DB;
        $tiicourseid = 0;

        if ($tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cmid,
                                                    'name' => 'turnitin_assignid'))) {
            $tiicourseid = $this->get_course_id_from_assignment_id($tiiassignment->value);
        } else {
            $coursemods = get_course_mods($courseid);
            foreach ($coursemods as $coursemod) {
                if ($coursemod->modname != 'turnitintooltwo') {
                    if ($tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $coursemod->id,
                                                                                        'name' => 'turnitin_assignid'))) {
                        $tiicourseid = $this->get_course_id_from_assignment_id($tiiassignment->value);
                    }
                }
            }
        }

        return ($tiicourseid > 0) ? $tiicourseid : false;
    }

    /**
     * Migrate course from previous version of plugin to this
     */
    public function migrate_previous_course($coursedata, $turnitincid, $workflowcontext = "site") {
        global $DB, $USER;

        $turnitincourse = new stdClass();
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
                turnitintooltwo_print_error('classupdateerror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
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

        STATIC $ppDisplayCount = 0;

        if (!$ppDisplayCount) {
            $numEvents = $DB->count_records_sql("SELECT count(*) FROM {events_queue} q
            LEFT JOIN {events_queue_handlers} h ON (h.queuedeventid = q.id)
            LEFT JOIN {events_handlers} e ON (h.handlerid = e.id)
            WHERE e.eventname IN ('assessable_file_uploaded', 'assessable_files_done', 'assessable_content_uploaded', 'assessable_submitted') AND component = 'plagiarism_turnitin'");

            mtrace(get_string("ppqueuesize", 'plagiarism_turnitin').': '. $numEvents);
            $ppDisplayCount = 1;
        }

        $result = true;
        // Remove the event if the course module no longer exists.
        if (!$cm = get_coursemodule_from_id($eventdata->modulename, $eventdata->cmid)) {
            return true;
        }

        // Initialise module settings.
        $plagiarismsettings = $this->get_settings($eventdata->cmid);
        $moduletiienabled = $this->get_config_settings('mod_'.$eventdata->modulename);
        if ($eventdata->modulename == 'assign') {
            $plagiarismsettings["plagiarism_draft_submit"] = (isset($plagiarismsettings["plagiarism_draft_submit"])) ?
                                                                $plagiarismsettings["plagiarism_draft_submit"] : 0;
        }

        // Either module not using Turnitin or Turnitin not being used at all so return true to remove event from queue.
        if (empty($plagiarismsettings['use_turnitin']) || empty($moduletiienabled)) {
            return true;
        }

        static $tiiconnection;
        if (empty($tiiconnection)) {
            if (!$tiiconnection = $this->test_turnitin_connection('cron')) {
                return false;
            }
        }

        // Get course data, return false if there is a problem creating it.
        $coursedata = $this->get_course_data($cm->id, $cm->course, 'cron');
        if (empty($coursedata->turnitin_cid)) {
            return false;
        }

        switch ($eventdata->event_type) {
            case "mod_created":
            case "mod_updated":
                $syncassignment = $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron");
                return true;
                break;

            case "file_uploaded":
            case "assessable_submitted":
            case "content_uploaded":
            case "files_done":
                // Initialise counter, limit submission events processing to
                // PLAGIARISM_TURNITIN_CRON_SUBMISSIONS_LIMIT per cron run.
                static $i;
                if (empty($i)) {
                    $i = 1;
                }

                // Only process submissions up to the processing limit.
                if ($i > PLAGIARISM_TURNITIN_CRON_SUBMISSIONS_LIMIT) {
                    return false;
                }

                $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
                if ($cm->modname != 'assign') {
                    $moduledata->submissiondrafts = 0;
                }

                // If draft submissions are turned on then only send to Turnitin if the draft submit setting is set.
                if ($moduledata->submissiondrafts && $plagiarismsettings["plagiarism_draft_submit"] == 1 &&
                    ($eventdata->event_type == 'file_uploaded' || $eventdata->event_type == 'content_uploaded')) {
                    return true;
                }

                // Get correct user. In assignments from Moodle 2.7, instructors could submit on behalf of students
                // but the eventdata->userid is still the user who made the submission.
                $submitter = $eventdata->userid;

                // Create module object.
                $moduleclass = "turnitin_".$cm->modname;
                $moduleobject = new $moduleclass;

                $author = $moduleobject->get_author($eventdata->itemid);
                $author = (!empty($author)) ? $author : $eventdata->userid;

                $errorcode = "";

                try {
                    // Join User to course.
                    $user = new turnitintooltwo_user($author, 'Learner', true, 'cron');
                    $user->join_user_to_class($coursedata->turnitin_cid);
                } catch (Exception $e) {
                    $user = new turnitintooltwo_user($author, 'Learner', 'false', 'cron', 'false');

                    $errorcode = 7;
                }

                $syncassignment = $this->sync_tii_assignment($cm, $coursedata->turnitin_cid, "cron", true);

                // Cron errorcode needs to be passed to submission function.
                if (!empty($syncassignment['errorcode'])) {
                    $cronerror = $syncassignment['errorcode'];
                } elseif (!empty($errorcode)) {
                    $cronerror = $errorcode;
                } else {
                    $cronerror = "";
                }

                // Get actual text content and files to be submitted for draft submissions
                // as this won't be present in eventdata for certain event types.
                if ($eventdata->modulename == 'assign' &&
                    ($eventdata->event_type == "files_done" || $eventdata->event_type == "assessable_submitted")) {

                    // Get content.
                    $moodlesubmission = $DB->get_record('assign_submission', array('assignment' => $cm->instance,
                                                'userid' => $author, 'id' => $eventdata->itemid), 'id');
                    if ($moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
                                                array('submission' => $moodlesubmission->id), 'onlinetext')) {
                        $eventdata->content = $moodletextsubmission->onlinetext;
                    }

                    // Get Files.
                    $eventdata->pathnamehashes = array();
                    $filesconditions = array('component' => 'assignsubmission_file',
                                            'itemid' => $moodlesubmission->id, 'userid' => $author);
                    if ($moodlefiles = $DB->get_records('files', $filesconditions)) {
                        foreach ($moodlefiles as $moodlefile) {
                            $eventdata->pathnamehashes[] = $moodlefile->pathnamehash;
                        }
                    }
                }

                // Attempt to submit text content to Turnitin.
                // If there was an error when creating the assignment then still attempt to process the submission so it can
                // be saved as failed and therefore doesn't cause the cron to get stuck.
                if (($eventdata->event_type == "content_uploaded" || $eventdata->event_type == "files_done" ||
                        $eventdata->event_type == "assessable_submitted")
                        && !empty($eventdata->content)) {

                    // Get extra data for text content submissions and remove unneeded events.
                    switch ($eventdata->modulename) {
                        case "assign":
                            if ($contentsubmission = $DB->get_record('assign_submission', array('userid' => $user->id,
                                                                        'assignment' => $moduledata->id,
                                                                        'id' => $eventdata->itemid))) {
                                $tempfilename = 'onlinetext_'.$user->id."_".$cm->id."_".$moduledata->id.'.txt';
                                $submissiontype = 'text_content';
                            } else {
                                // Content has been deleted but event not removed.
                                return true;
                            }

                            break;

                        case "forum":
                            if ($contentsubmission = $DB->get_record('forum_posts', array('id' => $eventdata->itemid))) {
                                $tempfilename = 'forumpost_'.$user->id."_".$cm->id."_".
                                                    $moduledata->id."_".$eventdata->itemid.'.txt';
                                $submissiontype = 'forum_post';
                            } else {
                                // Content has been deleted but event not removed.
                                return true;
                            }
                            break;

                        case 'workshop':
                            if ($moodlesubmission = $DB->get_record('workshop_submissions',
                                            array('id' => $eventdata->itemid))) {
                                $tempfilename = 'onlinetext_'.$user->id."_".$cm->id."_".$moduledata->id.'.txt';
                                $submissiontype = 'text_content';
                                $eventdata->content = $moodlesubmission->content;
                            } else {
                                // Content has been deleted but event not removed.
                                return true;
                            }
                            break;
                    }

                    $identifier = sha1($eventdata->content);

                    // Get previous text content details if it has been submitted previously.
                    $plagiarismfile = $DB->get_record('plagiarism_turnitin_files', array('userid' => $user->id, 'cm' => $cm->id,
                                                                                        'identifier' => $identifier));

                    if ($plagiarismfile) {
                        // Only submit if this content hasn't been submitted successfuly before.
                        if ($plagiarismfile->statuscode != "success") {
                            $result = $this->tii_submission($cm, $syncassignment['tiiassignmentid'], $user, $submitter,
                                                                $identifier, $submissiontype, $eventdata->itemid,
                                                                $tempfilename, $eventdata->content, $cronerror);
                        } else {
                            return true;
                        }
                    } else {
                        $result = $this->tii_submission($cm, $syncassignment['tiiassignmentid'], $user, $submitter,
                                                            $identifier, $submissiontype, $eventdata->itemid,
                                                            $tempfilename, $eventdata->content, $cronerror);
                    }
                }

                // Attempt to submit files to Turnitin.
                $result = $result && true;
                if (!empty($eventdata->pathnamehashes)) {
                    foreach ($eventdata->pathnamehashes as $pathnamehash) {
                        $fs = get_file_storage();
                        $file = $fs->get_file_by_hash($pathnamehash);

                        if (!$file) {
                            turnitintooltwo_activitylog('File not found: '.$pathnamehash, 'PP_NO_FILE');
                            $result = true;
                            continue;
                        } else {
                            try {
                                $file->get_content();
                            } catch (Exception $e) {
                                turnitintooltwo_activitylog('File content not found: '.$pathnamehash, 'PP_NO_FILE');
                                mtrace($e);
                                mtrace('File content not found. pathnamehash: '.$pathnamehash);
                                $result = true;
                                continue;
                            }
                        }

                        if ($file->get_filename() === '.') {
                            continue;
                        }

                        if ($this->check_if_submitting($cm, $author, $pathnamehash, 'file')) {
                            $result = $result && $this->tii_submission($cm, $syncassignment['tiiassignmentid'], $user, $submitter,
                                                                        $pathnamehash, 'file', $eventdata->itemid, '', '', $cronerror);
                        } else {
                            $result = $result && true;
                        }
                    }
                }

                // Output warning that no further submissions will be processed as processing limit has been reached.
                if ($i == PLAGIARISM_TURNITIN_CRON_SUBMISSIONS_LIMIT) {
                    mtrace(get_string('ppcronsubmissionlimitreached', 'plagiarism_turnitin',
                                            PLAGIARISM_TURNITIN_CRON_SUBMISSIONS_LIMIT));
                }
                $i++;

                break;
        }

        return $result;
    }

    /**
     * Initialise submission values
     *
     **/
    private function create_new_tii_submission($cm, $user, $identifier, $submissiontype) {
        global $DB;

        $plagiarismfile = new stdClass();
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

        $plagiarismfile = new stdClass();
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
    private function clean_old_turnitin_submissions($cm, $userid, $itemid, $submissiontype, $identifier, $user) {
        global $DB, $CFG;
        $currentfiles = array();
        $deletestr = '';

        // Create module object
        $moduleclass = "turnitin_".$cm->modname;
        $moduleobject = new $moduleclass;

        if ($submissiontype == 'file') {
            // If this is an assignment then we need to account for previous attempts so get other items ids.
            if ($cm->modname == 'assign') {
                $itemids = $DB->get_records('assign_submission', array(
                                                                    'assignment' => $cm->instance,
                                                                    'userid' => $userid
                                                                    ), '', 'id');

                // Only proceed if we have item ids.
                if (empty($itemids)) {
                    return true;
                } else {
                    list($itemidsinsql, $itemidsparams) = $DB->get_in_or_equal(array_keys($itemids));
                    $itemidsinsql = ' itemid '.$itemidsinsql;
                    $params = array_merge(array($moduleobject->filecomponent, $userid), $itemidsparams);
                }

            } else {
                $itemidsinsql = ' itemid = ? ';
                $params = array($moduleobject->filecomponent, $userid, $itemid);
            }

            if ($moodlefiles = $DB->get_records_select('files', " component = ? AND userid = ? AND source IS NOT null AND ".$itemidsinsql,
                                                    $params, 'id DESC', 'pathnamehash')) {
                list($notinsql, $notinparams) = $DB->get_in_or_equal(array_keys($moodlefiles), SQL_PARAMS_QM, 'param', false);
                $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";
                $oldfiles = $DB->get_records_select('plagiarism_turnitin_files', " userid = ? AND cm = ? ".
                                                                            " AND ".$typefield." = ? AND identifier ".$notinsql,
                                                        array_merge(array($userid, $cm->id, 'file'), $notinparams));

                if (!empty($oldfiles)) {
                    // Initialise Comms Object.
                    $turnitincomms = new turnitin_comms();
                    $turnitincall = $turnitincomms->initialise_api();

                    foreach ($oldfiles as $oldfile) {
                        // Delete submission from Turnitin if we have an external id.
                        if (!is_null($oldfile->externalid)) {
                            $this->delete_tii_submission($cm, $oldfile->externalid, $user);
                        }
                        $deletestr .= $oldfile->id.', ';
                    }

                    list($insql, $deleteparams) = $DB->get_in_or_equal(explode(',', substr($deletestr, 0, -2)));
                    $deletestr = " id ".$insql;
                }
            }

        } else if ($submissiontype == 'text_content') {
            $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";
            $deletestr = " userid = ? AND cm = ? AND ".$typefield." = ? AND identifier != ? ";
            $deleteparams = array($userid, $cm->id, 'text_content', $identifier);
        }

        // Delete from database.
        if (!empty($deletestr)) {
            $DB->delete_records_select('plagiarism_turnitin_files', $deletestr, $deleteparams);
        }
    }

    public function save_failed_submission($cm, $user, $submissionid, $identifier, $submissiontype,
                                            $errorcode, $previoussubmission) {
        global $DB;

        $plagiarismfile = new stdClass();
        if ($submissionid != 0) {
            $plagiarismfile->id = $submissionid;
        }
        $plagiarismfile->cm = $cm->id;
        $plagiarismfile->userid = $user->id;
        $plagiarismfile->identifier = $identifier;
        $plagiarismfile->statuscode = 'error';
        $plagiarismfile->errorcode = $errorcode;
        $plagiarismfile->attempt = (!empty($previoussubmission)) ? $previoussubmission->attempt + 1 : 1;
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

        mtrace('-------------------------');
        mtrace(get_string('errorcode'.$errorcode, 'plagiarism_turnitin').':');
        mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
        mtrace('Course Module: '.$cm->id.'');
        mtrace('-------------------------');

        return true;
    }

    /**
     * If there is no submission record then we are creating one. Text content should be submitted.
     * If a file has already been submitted then check whether the identifier is the same, if it is do nothing.
     * If it's not then either edit submission or create new one depending on module settings.
     */
    public function tii_submission($cm, $tiiassignmentid, $user, $submitter, $identifier, $submissiontype, $itemid = 0,
                                    $title = '', $textcontent = '', $cronerror = '') {
        global $CFG, $DB, $USER, $turnitinacceptedfiles;
        // Instantiate error code
        $errorcode = 0;

        // Get config, module and course settings that we need.
        $config = turnitintooltwo_admin_config();
        $settings = $this->get_settings($cm->id);
        $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
        $coursedata = $this->get_course_data($cm->id, $cm->course, 'cron');

        // Update user's details on Turnitin.
        $user->edit_tii_user();

        // Clean up old Turnitin submission files.
        if ($itemid != 0 && $submissiontype == 'file' && $cm->modname != 'forum') {
            $this->clean_old_turnitin_submissions($cm, $user->id, $itemid, $submissiontype, $identifier, $user);
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

                    try {
                        $textcontent = $file->get_content();
                    } catch (Exception $e) {
                        turnitintooltwo_activitylog('File content not found on submission: '.$pathnamehash, 'PP_NO_FILE');
                        mtrace($e);
                        mtrace('File content not found on submission. pathnamehash: '.$pathnamehash);
                        $errorcode = 9;
                    }
                } else {
                    // Check when text submission was last modified.
                    switch ($cm->modname) {
                        case 'assign':
                            $moodlesubmission = $DB->get_record('assign_submission',
                                                    array('assignment' => $cm->instance,
                                                                'userid' => $user->id,
                                                                'id' => $itemid), 'timemodified');
                            break;
                        case 'workshop':
                            $moodlesubmission = $DB->get_record('workshop_submissions',
                                                    array('workshopid' => $cm->instance,
                                                            'authorid' => $user->id), 'timemodified');
                            break;
                    }

                    $title = (!empty($title)) ? $title : 'onlinetext_'.$user->id."_".$cm->id."_".$cm->instance.'.txt';
                    $filename = (substr($title, -4) == '.txt') ? $title : $title.'.txt';
                    $textcontent = strip_tags($textcontent);
                    $timemodified = $moodlesubmission->timemodified;
                }

                // Get submission method depending on whether there has been a previous submission.
                $submissionfields = 'id, cm, externalid, identifier, statuscode, lastmodified, attempt';
                $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";

                // Double check there is only one submission.
                $previoussubmissions = $DB->get_records_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND ".$typefield." = ? AND identifier = ? ",
                                                array($cm->id, $user->id, $submissiontype, $identifier),
                                                    'id', $submissionfields);
                $previoussubmission = end($previoussubmissions);
                if ($previoussubmission) {
                    // Don't submit if submission hasn't changed.
                    if (in_array($previoussubmission->statuscode, array("success", "error"))
                            && $timemodified <= $previoussubmission->lastmodified) {
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
                    $typefield = ($CFG->dbtype == "oci") ? " to_char(submissiontype) " : " submissiontype ";
                    if ($submissiontype == 'text_content' &&
                            $previoussubmission = $DB->get_record_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND ".$typefield." = ? ",
                                                array($cm->id, $user->id, 'text_content'),
                                                    'id, cm, externalid, identifier, statuscode, lastmodified, attempt', 0, 1)) {

                        $submissionid = $previoussubmission->id;
                        $apimethod = (is_null($previoussubmission->externalid) || $settings["plagiarism_report_gen"] == 0)
                                            ? "createSubmission" : "replaceSubmission";

                        // Delete old text content submissions from Turnitin if not replacing.
                        if ($settings["plagiarism_report_gen"] == 0 && !is_null($previoussubmission->externalid)) {
                            $this->delete_tii_submission($cm, $previoussubmission->externalid, $user);
                        }

                        $this->reset_tii_submission($cm, $user, $identifier, $previoussubmission, $submissiontype);
                    } else {
                        $apimethod = "createSubmission";
                        $submissionid = $this->create_new_tii_submission($cm, $user, $identifier, $submissiontype);
                    }
                }

                // Remove any old text submissions here if there are any as there is only one per submission
                if ($itemid != 0 && $submissiontype == "text_content") {
                    $this->clean_old_turnitin_submissions($cm, $user->id, $itemid, $submissiontype, $identifier, $user);
                }

                break;

            case 'forum_post':
                if ($previoussubmissions = $DB->get_records_select('plagiarism_turnitin_files',
                                                    " cm = ? AND userid = ? AND identifier = ? ",
                                                    array($cm->id, $user->id, $identifier),
                                                    'id DESC', 'id, cm, externalid, identifier, statuscode, attempt', 0, 1)) {

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


        // Check file is less than maximum allowed size.
        if ($submissiontype == 'file') {
            if ($file->get_filesize() > TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE) {
                $errorcode = 2;
            }
        }

        // Don't submit if a user has not accepted the eula.
        if ($user->id == $submitter && $user->user_agreement_accepted != 1) {
            $errorcode = 3;
        }

        // If applicable, check whether file type is accepted.
        $acceptanyfiletype = (!empty($settings["plagiarism_allow_non_or_submissions"])) ? 1 : 0;
        if (!$acceptanyfiletype && $submissiontype == 'file') {
            $filenameparts = explode('.', $filename);
            $fileext = strtolower(end($filenameparts));
            if (!in_array(".".$fileext, $turnitinacceptedfiles)) {
                $errorcode = 4;
            }
        }

        // Read the stored file/content into a temp file for submitting.
        $submission_title = explode('.', $title);

        $file_string = array(
            $submission_title[0],
            $cm->id
        );

        // Only include user's name and id if we're not using blind marking and student privacy.
        if ( empty($moduledata->blindmarking) && empty($config->enablepseudo) ) {
            $user_details = array(
                $user->id,
                $user->firstname,
                $user->lastname
            );

            $file_string = array_merge($user_details, $file_string);
        }

        try {
            $tempfile = turnitintooltwo_tempfile($file_string, $filename);
        } catch (Exception $e) {
            $errorcode = 8;
        }

        // Any errors from cron processing take prioirity.
        if (!empty($cronerror)) {
            $errorcode = $cronerror;
        }

        // Save failed submission and don't process any further.
        if ($errorcode != 0) {
            return $this->save_failed_submission($cm, $user, $submissionid, $identifier,
                        $submissiontype, $errorcode, $previoussubmission);
        }

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

        // Account for submission by teacher in assignment module.
        if ($user->id == $submitter) {
            $submission->setSubmitterUserId($user->tii_user_id);
            $submission->setRole('Learner');
        } else {
            $instructor = new turnitintooltwo_user($submitter, 'Instructor');
            $instructor->edit_tii_user();

            $submission->setSubmitterUserId($instructor->tii_user_id);
            $submission->setRole('Instructor');
        }

        $submission->setSubmissionDataPath($tempfile);

        // Initialise Comms Object.
        $turnitincomms = new turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $response = $turnitincall->$apimethod($submission);
            $newsubmission = $response->getSubmission();
            $newsubmissionid = $newsubmission->getSubmissionId();

            $plagiarismfile = new stdClass();
            if ($apimethod == "replaceSubmission" || $submissionid != 0) {
                $plagiarismfile->id = $submissionid;
            }
            $plagiarismfile->cm = $cm->id;
            $plagiarismfile->userid = $user->id;
            $plagiarismfile->submitter = $submitter;
            $plagiarismfile->identifier = $identifier;
            $plagiarismfile->externalid = $newsubmissionid;
            $plagiarismfile->statuscode = 'success';
            $plagiarismfile->similarityscore = null;
            $plagiarismfile->attempt = (!empty($previoussubmission)) ? $previoussubmission->attempt + 1 : 1;
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

            // Delete the tempfile.
            if (!is_null($tempfile)) {
                unlink($tempfile);
            }

            // Add config field to show submissions have been made which we use to lock anonymous marking setting
            $configfield = new stdClass();
            $configfield->cm = $cm->id;
            $configfield->name = 'submitted';
            $configfield->value = 1;

            if (!$currentconfigfield = $DB->get_field('plagiarism_turnitin_config', 'id',
                                                 (array('cm' => $cm->id, 'name' => 'submitted')))) {
                if (!$DB->insert_record('plagiarism_turnitin_config', $configfield)) {
                    turnitintooltwo_print_error('defaultupdateerror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
                }
            }

            // Send a message to the user's Moodle inbox with the digital receipt.
            $receipt = new pp_receipt_message();
            $input = array(
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'submission_title' => $title,
                'assignment_name' => $moduledata->name,
                'course_fullname' => $coursedata->turnitin_ctl,
                'submission_date' => date('d-M-Y h:iA'),
                'submission_id' => $newsubmissionid
            );

            $message = $receipt->build_message($input);
            $receipt->send_message($user->id, $message);

            // Output a message in the cron for successfull submission to Turnitin.
            $outputvars = new stdClass();
            $outputvars->title = $title;
            $outputvars->submissionid = $newsubmissionid;
            $outputvars->assignmentname = $moduledata->name;
            $outputvars->coursename = $coursedata->turnitin_ctl;

            mtrace(get_string('cronsubmittedsuccessfully', 'plagiarism_turnitin', $outputvars));
        } catch (Exception $e) {
            $errorstring = (empty($previoussubmission->externalid)) ? "pp_createsubmissionerror" : "pp_updatesubmissionerror";

            $plagiarismfile = new stdClass();
            if ($submissionid != 0) {
                $plagiarismfile->id = $submissionid;
            }
            $plagiarismfile->attempt = (!empty($previoussubmission)) ? $previoussubmission->attempt + 1 : 1;
            $plagiarismfile->cm = $cm->id;
            $plagiarismfile->userid = $user->id;
            $plagiarismfile->submitter = $submitter;
            $plagiarismfile->identifier = $identifier;
            $plagiarismfile->statuscode = 'error';
            $plagiarismfile->lastmodified = time();
            $plagiarismfile->submissiontype = $submissiontype;
            $plagiarismfile->errorcode = 0;
            $plagiarismfile->errormsg = get_string('pp_submission_error', 'plagiarism_turnitin').' '.$e->getMessage();

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

            mtrace('-------------------------');
            mtrace(get_string('pp_submission_error', 'plagiarism_turnitin').': '.$e->getMessage());
            mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
            mtrace('Course Module: '.$cm->id.'');
            mtrace('-------------------------');
        }

        return true;
    }

    /**
     * Delete a submission from Turnitin
     */
    private function delete_tii_submission($cm, $submissionid, $user) {
        global $CFG;

        // Initialise Comms Object.
        $turnitincomms = new turnitin_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $submission = new TiiSubmission();
        $submission->setSubmissionId($submissionid);

        try {
            $response = $turnitincall->deleteSubmission($submission);
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'turnitindeletionerror', false);

            mtrace('-------------------------');
            mtrace(get_string('turnitindeletionerror', 'plagiarism_turnitin').': '.$e->getMessage());
            mtrace('User:  '.$user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->email.')');
            mtrace('Course Module: '.$cm->id.'');
            mtrace('-------------------------');
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
 * Remove submission data and config settings for module.
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

/**
 * Handle cron call from scheduled task
 */
function plagiarism_turnitin_cron() {
    $pluginturnitin = new plagiarism_plugin_turnitin();
    return $pluginturnitin->cron();
}
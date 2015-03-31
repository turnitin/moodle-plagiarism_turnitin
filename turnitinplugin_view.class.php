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

require_once($CFG->dirroot.'/mod/turnitintooltwo/turnitintooltwo_form.class.php');

global $tiipp;
$tiipp = new stdClass();
$tiipp->in_use = true;

class turnitinplugin_view {

    /**
     * Prints the tab menu for the plugin settings
     *
     * @param string $currenttab The currect tab to be styled as selected
     */
    public function draw_settings_tab_menu($currenttab, $notice = null) {
        global $OUTPUT;

        $tabs = array();
        $tabs[] = new tabobject('turnitinsettings', 'settings.php',
                        get_string('config', 'turnitintooltwo'), get_string('config', 'turnitintooltwo'), false);
        $tabs[] = new tabobject('turnitindefaults', 'settings.php?do=defaults',
                        get_string('defaults', 'turnitintooltwo'), get_string('defaults', 'turnitintooltwo'), false);
        $tabs[] = new tabobject('turnitinshowusage', 'settings.php?do=viewreport',
                        get_string('showusage', 'turnitintooltwo'), get_string('showusage', 'turnitintooltwo'), false);
        $tabs[] = new tabobject('turnitinsaveusage', 'settings.php?do=savereport',
                        get_string('saveusage', 'turnitintooltwo'), get_string('saveusage', 'turnitintooltwo'), false);
        $tabs[] = new tabobject('turnitinerrors', 'settings.php?do=errors',
                        get_string('errors', 'turnitintooltwo'), get_string('errors', 'turnitintooltwo'), false);
        print_tabs(array($tabs), $currenttab);

        if (!is_null($notice)) {
            echo $OUTPUT->box($notice["message"], 'generalbox boxaligncenter', $notice["type"]);
        }
    }

    /**
     * Show the config settings form for the plugin
     *
     * @param int $course_module_id
     * @param obj $turnitintooltwo
     * @return output
     */
    public function show_config_form($pluginconfig) {
        global $CFG, $OUTPUT;

        // Populate elements array which will generate the form elements
        // Each element is in following format: (type, name, label, helptext (minus _help), options (if select).
        $elements = array();
        $elements[] = array('header', 'config', get_string('turnitinconfig', 'turnitintooltwo'));
        $elements[] = array('html', get_string('tiiexplain', 'turnitintooltwo'));

        $elements[] = array('advcheckbox', 'turnitin_use', get_string('useturnitin', 'turnitintooltwo'), '', array(0, 1));

        // Enable Turnitin for specific modules
        $supported_mods = ($CFG->branch > 23) ? array('assign', 'forum', 'workshop') : '';
        foreach ($supported_mods as $mod) {
            $elements[] = array('checkbox', 'turnitin_use_mod_'.$mod, get_string('useturnitin_mod', 'turnitintooltwo', $mod), '', 
                                '', '', '', array('turnitin_use', '==', 1));
        }

        $elements[] = array('html', get_string('pp_configuredesc', 'turnitintooltwo', $CFG->wwwroot));

        $elements[] = array('hidden', 'action', 'config');
        $customdata["elements"] = $elements;
        $customdata["disable_form_change_checker"] = true;

        $optionsform = new turnitintooltwo_form($CFG->wwwroot.'/plagiarism/turnitin/settings.php', $customdata);

        $optionsform->set_data($pluginconfig);
        $output = $optionsform->display();

        return $output;
    }

    /**
     * Due to moodle's internal plugin hooks we can not use our bespoke form class
     * for Turnitin settings. This form shows in settings > defaults as well as the
     * activity creation screen.
     *
     * @global type $CFG
     * @param type $plugin_defaults
     * @return type
     */
    public function add_elements_to_settings_form($mform, $location = "activity", $cmid = 0, $currentrubric = 0) {
        global $CFG, $OUTPUT, $PAGE, $USER, $DB;

        $PAGE->requires->string_for_js('changerubricwarning', 'turnitintooltwo');
        $config = turnitintooltwo_admin_config();
        $config_warning = '';

        $instructor = new turnitintooltwo_user($USER->id, 'Instructor');
        $instructorrubrics = $instructor->get_instructor_rubrics();

        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $genoptions = array(0 => get_string('genimmediately1', 'turnitintooltwo'),
                            1 => get_string('genimmediately2', 'turnitintooltwo'),
                            2 => get_string('genduedate', 'turnitintooltwo'));
        $excludetypeoptions = array( 0 => get_string('no'), 1 => get_string('excludewords', 'turnitintooltwo'),
                            2 => get_string('excludepercent', 'turnitintooltwo'));

        if ($location == "defaults") {
            $mform->addElement('header', 'plugin_header', get_string('turnitindefaults', 'turnitintooltwo'));
            $mform->addElement('html', get_string("defaultsdesc", "turnitintooltwo"));
        }

        if ($location != "defaults") {
            $mform->addElement('header', 'plugin_header', get_string('turnitinpluginsettings', 'turnitintooltwo'));

            // Add in custom Javascript and CSS.
            if ($CFG->branch <= 25) {
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery-1.8.2.min.js');
                $PAGE->requires->js($jsurl, true);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/turnitintooltwo.js');
                $PAGE->requires->js($jsurl, true);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/plagiarism_plugin.js');
                $PAGE->requires->js($jsurl, true);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery-ui-1.10.4.custom.min.js');
                $PAGE->requires->js($jsurl, true);
                $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery.colorbox.js');
                $PAGE->requires->js($jsurl, true);
            } else {
                $PAGE->requires->jquery();
                $PAGE->requires->jquery_plugin('ui');
                $PAGE->requires->jquery_plugin('turnitintooltwo-turnitintooltwo', 'mod_turnitintooltwo');
                $PAGE->requires->jquery_plugin('turnitintooltwo-plagiarism_plugin', 'mod_turnitintooltwo');
                $PAGE->requires->jquery_plugin('turnitintooltwo-colorbox', 'mod_turnitintooltwo');
            }

            $cssurl = new moodle_url('/mod/turnitintooltwo/css/styles.css');
            $PAGE->requires->css($cssurl);
            $cssurl = new moodle_url('/mod/turnitintooltwo/css/styles_pp.css');
            $PAGE->requires->css($cssurl);
            $cssurl = new moodle_url('/mod/turnitintooltwo/css/colorbox.css');
            $PAGE->requires->css($cssurl);
            $cssurl = new moodle_url('/mod/turnitintooltwo/css/font-awesome.min.css');
            $PAGE->requires->css($cssurl);

            if (empty($config->accountid) || empty($config->secretkey) || empty($config->apiurl)) {
                $config_warning = html_writer::tag('div', get_string('configureerror', 'turnitintooltwo'), 
                                                    array('class' => 'library_not_present_warning'));
            }

            if ($config_warning != '') {
                $mform->addElement('html', $config_warning);
            }

            // Refresh Grades
            $refreshgrades = '';
            if ($cmid != 0) {
                // If assignment has submissions then show a refresh grades button
                $numsubs = $DB->count_records('plagiarism_turnitin_files', array('cm' => $cmid));
                if ($numsubs > 0) {
                    $refreshgrades = html_writer::tag('div', html_writer::tag('i', '', array('class' => 'fa fa-refresh fa-2x',
                                                    'title' => get_string('turnitinrefreshsubmissions', 'turnitintooltwo'))).
                                                html_writer::tag('span', get_string('turnitinrefreshsubmissions', 'turnitintooltwo')),
                                                                    array('class' => 'plagiarism_turnitin_refresh_grades'));

                    $refreshgrades .= html_writer::tag('div', html_writer::tag('i', '', array('class' => 'fa fa-spinner fa-spin fa-2x',
                                                    'title' => get_string('turnitinrefreshingsubmissions', 'turnitintooltwo'))).
                                                html_writer::tag('span', get_string('turnitinrefreshingsubmissions', 'turnitintooltwo')),
                                                                    array('class' => 'plagiarism_turnitin_refreshing_grades'));
                }
            }

            // Quickmark Manager.
            $quickmarkmanagerlink = '';
            if ($config->usegrademark) {
                $quickmarkmanagerlink .= $OUTPUT->box_start('row_quickmark_manager', '');
                $quickmarkmanagerlink .= html_writer::link($CFG->wwwroot.
                                                '/mod/turnitintooltwo/extras.php?cmd=quickmarkmanager&view_context=box',
                                                get_string('launchquickmarkmanager', 'turnitintooltwo'),
                                                array('class' => 'plagiarism_turnitin_quickmark_manager_launch',
                                                    'title' => get_string('launchquickmarkmanager', 'turnitintooltwo')));
                $quickmarkmanagerlink .= html_writer::tag('span', '',
                                                array('class' => 'launch_form', 'id' => 'quickmark_manager_form'));
                $quickmarkmanagerlink .= $OUTPUT->box_end(true);
            }

            // Peermark Manager.
            $peermarkmanagerlink = '';
            if ($config->enablepeermark) {
                if ($cmid != 0) {
                    $peermarkmanagerlink .= $OUTPUT->box_start('row_peermark_manager', '');
                    $peermarkmanagerlink .= html_writer::link($CFG->wwwroot.
                                                    '/plagiarism/turnitin/ajax.php?cmid='.$cmid.
                                                        '&action=peermarkmanager&view_context=box',
                                                    get_string('launchpeermarkmanager', 'turnitintooltwo'),
                                                    array('class' => 'peermark_manager_launch',
                                                            'id' => 'peermark_manager_'.$cmid,
                                                            'title' => get_string('launchpeermarkmanager', 'turnitintooltwo')));
                    $peermarkmanagerlink .= html_writer::tag('span', '', array('class' => 'launch_form',
                                                                            'id' => 'peermark_manager_form'));
                    $peermarkmanagerlink .= $OUTPUT->box_end(true);
                }
            }

            if (!empty($quickmarkmanagerlink) || !empty($peermarkmanagerlink) || !empty($refreshgrades)) {
                $mform->addElement('static', 'static', '', $refreshgrades.$quickmarkmanagerlink.$peermarkmanagerlink);
            }
        }

        $locks  = $DB->get_records_sql("SELECT name,value FROM {plagiarism_turnitin_config} WHERE cm = 0");

        if (empty($config_warning)) {
            $mform->addElement('select', 'use_turnitin', get_string("useturnitin", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);

            $mform->addElement('select', 'plagiarism_show_student_report', get_string("studentreports", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addHelpButton('plagiarism_show_student_report', 'studentreports', 'turnitintooltwo');

            if ($mform->elementExists('submissiondrafts') || $location == 'defaults') {
                $tiidraftoptions = array(0 => get_string("submitondraft", "turnitintooltwo"), 
                                         1 => get_string("submitonfinal", "turnitintooltwo"));

                $mform->addElement('select', 'plagiarism_draft_submit', get_string("draftsubmit", "turnitintooltwo"), $tiidraftoptions);
                $this->lock($mform, $location, $locks);
                $mform->disabledIf('plagiarism_draft_submit', 'submissiondrafts', 'eq', 0);
            }

            $mform->addElement('select', 'plagiarism_allow_non_or_submissions', get_string("allownonor", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addHelpButton('plagiarism_allow_non_or_submissions', 'allownonor', 'turnitintooltwo');

            $suboptions = array(0 => get_string('norepository', 'turnitintooltwo'),
                                1 => get_string('standardrepository', 'turnitintooltwo'));
            switch ($config->repositoryoption) {
                case 0; // Standard options
                    $mform->addElement('select', 'plagiarism_submitpapersto', get_string('submitpapersto', 'turnitintooltwo'), $suboptions);
                    $this->lock($mform, $location, $locks);
                    break;
                case 1; // Standard options + Allow Instituional Repository
                    $suboptions[2] = get_string('institutionalrepository', 'turnitintooltwo');

                    $mform->addElement('select', 'plagiarism_submitpapersto', get_string('submitpapersto', 'turnitintooltwo'), $suboptions);
                    $this->lock($mform, $location, $locks);
                    break;
                case 2; // Force Standard Repository
                    $mform->addElement('hidden', 'plagiarism_submitpapersto', 1);
                    $mform->setType('plagiarism_submitpapersto', PARAM_RAW);
                    break;
                case 3; // Force No Repository
                    $mform->addElement('hidden', 'plagiarism_submitpapersto', 0);
                    $mform->setType('plagiarism_submitpapersto', PARAM_RAW);
                    break;
            }

            $mform->addElement('select', 'plagiarism_compare_student_papers', get_string("spapercheck", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addElement('select', 'plagiarism_compare_internet', get_string("internetcheck", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addElement('select', 'plagiarism_compare_journals', get_string("journalcheck", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);

            if ($config->repositoryoption == 1) {
                $mform->addElement('select', 'plagiarism_compare_institution',
                                                get_string('compareinstitution', 'turnitintooltwo'), $options);
                $this->lock($mform, $location, $locks);
            }

            $mform->addElement('select', 'plagiarism_report_gen', get_string("reportgenspeed", "turnitintooltwo"), $genoptions);
            $this->lock($mform, $location, $locks);
            $mform->addElement('select', 'plagiarism_exclude_biblio', get_string("excludebiblio", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addElement('select', 'plagiarism_exclude_quoted', get_string("excludequoted", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);

            $mform->addElement('select', 'plagiarism_exclude_matches', get_string("excludevalue", "turnitintooltwo"),
                                                                                $excludetypeoptions);
            $this->lock($mform, $location, $locks);
            $mform->addElement('text', 'plagiarism_exclude_matches_value', '');
            $mform->setType('plagiarism_exclude_matches_value', PARAM_INT);
            $mform->addRule('plagiarism_exclude_matches_value', null, 'numeric', null, 'client');
            $mform->disabledIf('plagiarism_exclude_matches_value', 'plagiarism_exclude_matches', 'eq', 0);

            if ($location == 'defaults'){
                $mform->addElement('text', 'plagiarism_locked_message', get_string("locked_message", "turnitintooltwo"), 'maxlength="50" size="50"' );
                $mform->setType('plagiarism_locked_message', PARAM_TEXT);
                $mform->setDefault('plagiarism_locked_message', get_string("locked_message_default", "turnitintooltwo") );
                $mform->addHelpButton('plagiarism_locked_message', 'locked_message', 'turnitintooltwo');
            }

            if ($location == "activity" && $config->usegrademark) {
                // Populate Rubric options.
                $rubricoptions = array('' => get_string('norubric', 'turnitintooltwo')) + $instructorrubrics;
                if (!empty($currentrubric)) {
                    $rubricoptions[$currentrubric] = (isset($rubricoptions[$currentrubric])) ?
                                    $rubricoptions[$currentrubric] : get_string('otherrubric', 'turnitintooltwo');
                }

                $mform->addElement('select', 'plagiarism_rubric', get_string('attachrubric', 'turnitintooltwo'), $rubricoptions);

                $mform->addElement('static', 'rubric_link', '',
                                        html_writer::link($CFG->wwwroot.
                                                    '/mod/turnitintooltwo/extras.php?cmd=rubricmanager&view_context=box',
                                                    get_string('launchrubricmanager', 'turnitintooltwo'),
                                                    array('class' => 'rubric_manager_launch',
                                                        'title' => get_string('launchrubricmanager', 'turnitintooltwo'))).
                                                    html_writer::tag('span', '', array('class' => 'launch_form',
                                                                                    'id' => 'rubric_manager_form')));
                $mform->setDefault('plagiarism_rubric', '');

                $mform->addElement('hidden', 'rubric_warning_seen', '');
                $mform->setType('rubric_warning_seen', PARAM_RAW);

                $mform->addElement('static', 'rubric_note', '', get_string('attachrubricnote', 'turnitintooltwo'));
            } else {
                $mform->addElement('hidden', 'plagiarism_rubric', '');
                $mform->setType('plagiarism_rubric', PARAM_RAW);
            }

            if (!empty($config->useerater)) {
                $handbookoptions = array(
                                            1 => get_string('erater_handbook_advanced', 'turnitintooltwo'),
                                            2 => get_string('erater_handbook_highschool', 'turnitintooltwo'),
                                            3 => get_string('erater_handbook_middleschool', 'turnitintooltwo'),
                                            4 => get_string('erater_handbook_elementary', 'turnitintooltwo'),
                                            5 => get_string('erater_handbook_learners', 'turnitintooltwo')
                                        );

                $dictionaryoptions = array(
                                            'en_US' => get_string('erater_dictionary_enus', 'turnitintooltwo'),
                                            'en_GB' => get_string('erater_dictionary_engb', 'turnitintooltwo'),
                                            'en'    => get_string('erater_dictionary_en', 'turnitintooltwo')
                                        );
                $mform->addElement('select', 'plagiarism_erater', get_string('erater', 'turnitintooltwo'), $options);
                $mform->setDefault('plagiarism_erater', 0);

                $mform->addElement('select', 'plagiarism_erater_handbook', get_string('erater_handbook', 'turnitintooltwo'),
                                                $handbookoptions);
                $mform->setDefault('plagiarism_erater_handbook', 2);
                $mform->disabledIf('plagiarism_erater_handbook', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('select', 'plagiarism_erater_dictionary', get_string('erater_dictionary', 'turnitintooltwo'),
                                                $dictionaryoptions);
                $mform->setDefault('plagiarism_erater_dictionary', 'en_US');
                $mform->disabledIf('plagiarism_erater_dictionary', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_spelling', get_string('erater_categories', 'turnitintooltwo'),
                                                " ".get_string('erater_spelling', 'turnitintooltwo'));
                $mform->disabledIf('plagiarism_erater_spelling', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_grammar', '', " ".get_string('erater_grammar', 'turnitintooltwo'));
                $mform->disabledIf('plagiarism_erater_grammar', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_usage', '', " ".get_string('erater_usage', 'turnitintooltwo'));
                $mform->disabledIf('plagiarism_erater_usage', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_mechanics', '', " ".
                                                get_string('erater_mechanics', 'turnitintooltwo'));
                $mform->disabledIf('plagiarism_erater_mechanics', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_style', '', " ".get_string('erater_style', 'turnitintooltwo'));
                $mform->disabledIf('plagiarism_erater_style', 'plagiarism_erater', 'eq', 0);
            }

            if ($config->useanon) {
                $mform->addElement('select', 'plagiarism_anonymity', get_string("turnitinanon", "turnitintooltwo"), $options);
                $mform->addElement('static', 'plagiarism_anonymous_note', '', get_string('ppanonmarkingnote', 'turnitintooltwo'));
            } else {
                $mform->addElement('hidden', 'plagiarism_anonymity', 0);
            }
            $mform->setType('plagiarism_anonymity', PARAM_INT);

            if ($config->transmatch) {
                $mform->addElement('select', 'plagiarism_transmatch', get_string("transmatch", "turnitintooltwo"), $options);
            } else {
                $mform->addElement('hidden', 'plagiarism_transmatch', 0);
            }
            $mform->setType('plagiarism_transmatch', PARAM_INT);

            $mform->addElement('hidden', 'action', "defaults");
            $mform->setType('action', PARAM_RAW);
        } else {
            $mform->addElement('hidden', 'use_turnitin', 0);
            $mform->setType('use_turnitin', PARAM_INT);
        }

        // Disable the form change checker - added in 2.3.2.
        if (is_callable(array($mform, 'disable_form_change_checker'))) {
            $mform->disable_form_change_checker();
        }
    }

    public function show_file_errors_table() {
        global $CFG, $OUTPUT;

        $plagiarismpluginturnitin = new plagiarism_plugin_turnitin();
        $files = $plagiarismpluginturnitin->get_file_upload_errors();

        // Do the table headers.
        $cells = array();
        $cells["id"] = new html_table_cell(get_string('id', 'turnitintooltwo'));
        $cells["user"] = new html_table_cell(get_string('student', 'turnitintooltwo'));
        $cells["user"]->attributes['class'] = 'left';
        $cells["course"] = new html_table_cell(get_string('course', 'turnitintooltwo'));
        $cells["module"] = new html_table_cell(get_string('module', 'turnitintooltwo'));
        $cells["file"] = new html_table_cell(get_string('file'));
        $cells["error"] = new html_table_cell(get_string('error'));
        $cells["delete"] = new html_table_cell('&nbsp;');
        $cells["delete"]->attributes['class'] = 'centered_cell';

        $table = new html_table();
        $table->id = "ppErrors";
        $table->head = $cells;

        $i = 0;
        $rows = array();

        if (count($files) == 0) {
            $cells = array();
            $cells["id"] = new html_table_cell(get_string('semptytable', 'turnitintooltwo'));
            $cells["id"]->colspan = 7;
            $cells["id"]->attributes['class'] = 'centered_cell';
            $rows[0] = new html_table_row($cells);
        } else {
            foreach ($files as $k => $v) {
                $cells = array();
                if (!empty($v->moduletype)) {

                    $cm = get_coursemodule_from_id($v->moduletype, $v->cm);

                    $cells["id"] = new html_table_cell($k);
                    $cells["user"] = new html_table_cell($v->firstname." ".$v->lastname." (".$v->email.")");

                    $courselink = new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $v->courseid));
                    $cells["course"] = new html_table_cell(html_writer::link($courselink,
                                                                $v->coursename, array('title' => $v->coursename)));

                    $modulelink = new moodle_url($CFG->wwwroot.'/mod/'.$v->moduletype.'/view.php', array('id' => $v->cm));
                    $cells["module"] = new html_table_cell(html_writer::link($modulelink, $cm->name, array('title' => $cm->name)));

                    if ($v->submissiontype == "file") {
                        $fs = get_file_storage();
                        if ($file = $fs->get_file_by_hash($v->identifier)) {
                            $cells["file"] = new html_table_cell(html_writer::link($CFG->wwwroot.'/pluginfile.php/'.
                                                    $file->get_contextid().'/'.$file->get_component().'/'.$file->get_filearea().'/'.
                                                    $file->get_itemid().'/'.$file->get_filename(),
                                                    $OUTPUT->pix_icon('fileicon', 'open '.$file->get_filename(), 'mod_turnitintooltwo').
                                                        " ".$file->get_filename()));
                        } else {
                            $cells["file"] = get_string('filedoesnotexist', 'turnitintooltwo');
                        }
                    } else {
                        $cells["file"] = str_replace('_', ' ', ucfirst($v->submissiontype));
                    }

                    $errorcode = $v->errorcode;
                    // Deal with legacy error issues.
                    if (is_null($errorcode)) {
                        $errorcode = 0;
                        if ($v->submissiontype == 'file') {
                            if (is_object($file) && $file->get_filesize() > TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE) {
                                $errorcode = 2;
                            }
                        }
                    }

                    // Show error message if there is one.
                    $errormsg = $v->errormsg;
                    if ($errorcode == 0) {
                        $errorstring = (is_null($errormsg)) ? get_string('ppsubmissionerrorseelogs', 'turnitintooltwo') : $errormsg;
                    } else {
                        $errorstring = get_string('errorcode'.$v->errorcode, 
                                            'turnitintooltwo', display_size(TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE));
                    }
                    $cells["error"] = $errorstring;

                    $fnd = array("\n", "\r");
                    $rep = array('\n', '\r');
                    $string = str_replace($fnd, $rep, get_string('deleteconfirm', 'turnitintooltwo'));

                    $attributes["onclick"] = "return confirm('".$string."');";
                    $cells["delete"] = new html_table_cell(html_writer::link($CFG->wwwroot.
                                            '/plagiarism/turnitin/settings.php?do=errors&action=deletefile&id='.$k,
                                            $OUTPUT->pix_icon('delete', get_string('deletesubmission', 'turnitintooltwo'),
                                                'mod_turnitintooltwo'), $attributes));
                    $cells["delete"]->attributes['class'] = 'centered_cell';

                    $rows[$i] = new html_table_row($cells);
                    $i++;
                }
            }

            if ($i == 0) {
                $cells = array();
                $cells["id"] = new html_table_cell(get_string('semptytable', 'turnitintooltwo'));
                $cells["id"]->colspan = 7;
                $cells["id"]->attributes['class'] = 'centered_cell';
                $rows[0] = new html_table_row($cells);
            }
        }
        $table->data = $rows;
        $output = html_writer::table($table);

        return $output;
    }

    /**
     * This adds a site lock check to the most recently added field
     */
    public function lock($mform, $location, $locks) {

        $field = end($mform->_elements)->_attributes['name'];
        if ($location == 'defaults'){
            // If we are on the site config level, show the lock UI
            $mform->addElement('advcheckbox', $field . '_lock', '', get_string('locked', 'admin'), array('group' => 1) );

        } else {

            // If we are at the plugin level, and we are locked then freeze
            $locked = (isset($locks[$field.'_lock']->value)) ? $locks[$field.'_lock']->value : 0;
            if ($locked) {
                $mform->freeze($field);
                // Show custom message why.
                $msg = $locks['plagiarism_locked_message']->value;
                if ($msg) {
                    $mform->addElement('static', $field . '_why', '', $msg );
                }
            }
        }
    }
}

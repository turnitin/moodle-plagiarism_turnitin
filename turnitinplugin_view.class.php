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
require_once(__DIR__.'/lib.php');

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
                        get_string('config', 'plagiarism_turnitin'), get_string('config', 'plagiarism_turnitin'), false);
        $tabs[] = new tabobject('turnitindefaults', 'settings.php?do=defaults',
                        get_string('defaults', 'plagiarism_turnitin'), get_string('defaults', 'plagiarism_turnitin'), false);
        $tabs[] = new tabobject('turnitinshowusage', 'settings.php?do=viewreport',
                        get_string('showusage', 'plagiarism_turnitin'), get_string('showusage', 'plagiarism_turnitin'), false);
        $tabs[] = new tabobject('turnitinsaveusage', 'settings.php?do=savereport',
                        get_string('saveusage', 'plagiarism_turnitin'), get_string('saveusage', 'plagiarism_turnitin'), false);
        $tabs[] = new tabobject('turnitinerrors', 'settings.php?do=errors',
                        get_string('errors', 'plagiarism_turnitin'), get_string('errors', 'plagiarism_turnitin'), false);
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
        global $CFG, $DB, $OUTPUT;

        // Populate elements array which will generate the form elements
        // Each element is in following format: (type, name, label, helptext (minus _help), options (if select).
        $elements = array();
        $elements[] = array('header', 'config', get_string('turnitinconfig', 'plagiarism_turnitin'));
        $elements[] = array('html', get_string('tiiexplain', 'plagiarism_turnitin'));

        $elements[] = array('advcheckbox', 'turnitin_use', get_string('useturnitin', 'plagiarism_turnitin'), '', array(0, 1));

        // Enable Turnitin for specific modules
        $supported_mods = array('assign', 'forum', 'workshop');
		
        if ($DB->record_exists('modules',array('name'=>'coursework','visible'=>1))) {
            $supported_mods[]   =   'coursework';
        }

        foreach ($supported_mods as $mod) {
            $elements[] = array('checkbox', 'turnitin_use_mod_'.$mod, get_string('useturnitin_mod', 'plagiarism_turnitin', $mod), '',
                                '', '', '', array('turnitin_use', '==', 1));
        }

        $elements[] = array('html', get_string('pp_configuredesc', 'plagiarism_turnitin', $CFG->wwwroot));

        $elements[] = array('hidden', 'action', 'config');
        $customdata["elements"] = $elements;
        $customdata["disable_form_change_checker"] = true;

        $optionsform = new turnitintooltwo_form($CFG->wwwroot.'/plagiarism/turnitin/settings.php', $customdata);

        $optionsform->set_data($pluginconfig);
        $output = $optionsform->display();

        return $output;
    }

    /**
     * Due to moodle's internal plugin hooks we can not use our bespoke form class for Turnitin
     * settings. This form shows in settings > defaults as well as the activity creation screen.
     *
     * @global type $CFG
     * @param type $plugin_defaults
     * @return type
     */
    public function add_elements_to_settings_form($mform, $course, $location = "activity", $cmid = 0, $currentrubric = 0) {
        global $CFG, $OUTPUT, $PAGE, $USER, $DB;

        // Include JS strings (closebutton is needed from both plugins).
        $PAGE->requires->string_for_js('changerubricwarning', 'plagiarism_turnitin');
        $PAGE->requires->string_for_js('closebutton', 'turnitintooltwo');
        $PAGE->requires->string_for_js('closebutton', 'plagiarism_turnitin');

        $config = turnitintooltwo_admin_config();
        $config_warning = '';
        $rubrics = array();

        if ($location == "activity") {
            $instructor = new turnitintooltwo_user($USER->id, 'Instructor');

            $instructor->join_user_to_class($course->turnitin_cid);
            $rubrics = $instructor->get_instructor_rubrics();

            // Get rubrics that are shared on the account.
            $turnitinclass = new turnitin_class($course->id);
            $turnitinclass->sharedrubrics = array();
            $turnitinclass->read_class_from_tii();

            // Merge the arrays, prioitising instructor owned arrays.
            $rubrics = $rubrics + $turnitinclass->sharedrubrics;
        }

        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $genoptions = array(0 => get_string('genimmediately1', 'plagiarism_turnitin'),
                            1 => get_string('genimmediately2', 'plagiarism_turnitin'),
                            2 => get_string('genduedate', 'plagiarism_turnitin'));
        $excludetypeoptions = array( 0 => get_string('no'), 1 => get_string('excludewords', 'plagiarism_turnitin'),
                            2 => get_string('excludepercent', 'plagiarism_turnitin'));

        if ($location == "defaults") {
            $mform->addElement('header', 'plugin_header', get_string('turnitindefaults', 'plagiarism_turnitin'));
            $mform->addElement('html', get_string("defaultsdesc", "turnitintooltwo"));
        }

        if ($location != "defaults") {
            $mform->addElement('header', 'plugin_header', get_string('turnitinpluginsettings', 'plagiarism_turnitin'));

            // Add in custom Javascript and CSS.
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('turnitintooltwo-turnitintooltwo', 'mod_turnitintooltwo');
            $PAGE->requires->jquery_plugin('plagiarism-turnitin_module', 'plagiarism_turnitin');
            $PAGE->requires->jquery_plugin('turnitintooltwo-colorbox', 'mod_turnitintooltwo');

            $cssurl = new moodle_url('/mod/turnitintooltwo/css/colorbox.css');
            $PAGE->requires->css($cssurl);

            // Refresh Grades
            $refreshgrades = '';
            if ($cmid != 0) {
                // If assignment has submissions then show a refresh grades button
                $numsubs = $DB->count_records('plagiarism_turnitin_files', array('cm' => $cmid));
                if ($numsubs > 0) {
                    $refreshgrades = html_writer::tag('div', $OUTPUT->pix_icon('refresh', get_string('turnitinrefreshsubmissions', 'plagiarism_turnitin'), 'plagiarism_turnitin').
                                                html_writer::tag('span', get_string('turnitinrefreshsubmissions', 'plagiarism_turnitin')),
                                                                    array('class' => 'plagiarism_turnitin_refresh_grades'));

                    $refreshgrades .= html_writer::tag('div', $OUTPUT->pix_icon('loading', get_string('turnitinrefreshingsubmissions', 'plagiarism_turnitin'), 'plagiarism_turnitin').
                                                html_writer::tag('span', get_string('turnitinrefreshingsubmissions', 'plagiarism_turnitin')),
                                                                    array('class' => 'plagiarism_turnitin_refreshing_grades'));
                }
            }

            // Quickmark Manager.
            $quickmarkmanagerlink = '';
            if ($config->usegrademark) {
                $quickmarkmanagerlink .= $OUTPUT->box_start('row_quickmark_manager', '');
                $quickmarkmanagerlink .= html_writer::link($CFG->wwwroot.
                                                '/mod/turnitintooltwo/extras.php?cmd=quickmarkmanager&view_context=box',
                                                get_string('launchquickmarkmanager', 'plagiarism_turnitin'),
                                                array('class' => 'plagiarism_turnitin_quickmark_manager_launch',
                                                    'title' => get_string('launchquickmarkmanager', 'plagiarism_turnitin')));
                $quickmarkmanagerlink .= html_writer::tag('span', '',
                                                array('class' => 'launch_form', 'id' => 'quickmark_manager_form'));
                $quickmarkmanagerlink .= $OUTPUT->box_end(true);
            }

            $use_turnitin = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cmid, 'name' => 'use_turnitin'));

            // Peermark Manager.
            $peermarkmanagerlink = '';
            if (!empty($config->enablepeermark) && !empty($use_turnitin->value)) {
                if ($cmid != 0) {
                    $peermarkmanagerlink .= $OUTPUT->box_start('row_peermark_manager', '');
                    $peermarkmanagerlink .= html_writer::link($CFG->wwwroot.
                                                    '/plagiarism/turnitin/ajax.php?cmid='.$cmid.
                                                        '&action=peermarkmanager&view_context=box',
                                                    get_string('launchpeermarkmanager', 'plagiarism_turnitin'),
                                                    array('class' => 'peermark_manager_launch',
                                                            'id' => 'peermark_manager_'.$cmid,
                                                            'title' => get_string('launchpeermarkmanager', 'plagiarism_turnitin')));
                    $peermarkmanagerlink .= html_writer::tag('span', '', array('class' => 'launch_form',
                                                                            'id' => 'peermark_manager_form'));
                    $peermarkmanagerlink .= $OUTPUT->box_end(true);
                }
            }

            if (!empty($quickmarkmanagerlink) || !empty($peermarkmanagerlink) || !empty($refreshgrades)) {
                $mform->addElement('static', 'static', '', $refreshgrades.$quickmarkmanagerlink.$peermarkmanagerlink);
            }
        }

        $locks = $DB->get_records_sql("SELECT name, value FROM {plagiarism_turnitin_config} WHERE cm IS NULL");

        if (empty($config_warning)) {
            $mform->addElement('select', 'use_turnitin', get_string("useturnitin", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);

            $mform->addElement('select', 'plagiarism_show_student_report', get_string("studentreports", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addHelpButton('plagiarism_show_student_report', 'studentreports', 'plagiarism_turnitin');

            if ($mform->elementExists('submissiondrafts') || $location == 'defaults') {
                $tiidraftoptions = array(0 => get_string("submitondraft", "turnitintooltwo"),
                                         1 => get_string("submitonfinal", "turnitintooltwo"));

                $mform->addElement('select', 'plagiarism_draft_submit', get_string("draftsubmit", "turnitintooltwo"), $tiidraftoptions);
                $this->lock($mform, $location, $locks);
                $mform->disabledIf('plagiarism_draft_submit', 'submissiondrafts', 'eq', 0);
            }

            $mform->addElement('select', 'plagiarism_allow_non_or_submissions', get_string("allownonor", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addHelpButton('plagiarism_allow_non_or_submissions', 'allownonor', 'plagiarism_turnitin');

            $suboptions = array(0 => get_string('norepository', 'plagiarism_turnitin'),
                                1 => get_string('standardrepository', 'plagiarism_turnitin'));
            switch ($config->repositoryoption) {
                case 0; // Standard options
                    $mform->addElement('select', 'plagiarism_submitpapersto', get_string('submitpapersto', 'plagiarism_turnitin'), $suboptions);
                    $this->lock($mform, $location, $locks);
                    break;
                case 1; // Standard options + Allow Instituional Repository
                    $suboptions[2] = get_string('institutionalrepository', 'plagiarism_turnitin');

                    $mform->addElement('select', 'plagiarism_submitpapersto', get_string('submitpapersto', 'plagiarism_turnitin'), $suboptions);
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

            $mform->addElement('html', html_writer::tag('div', get_string('checkagainstnote', 'plagiarism_turnitin'),
                                                                                array('class' => 'tii_checkagainstnote')));

            $mform->addElement('select', 'plagiarism_compare_student_papers', get_string("spapercheck", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addElement('select', 'plagiarism_compare_internet', get_string("internetcheck", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);
            $mform->addElement('select', 'plagiarism_compare_journals', get_string("journalcheck", "turnitintooltwo"), $options);
            $this->lock($mform, $location, $locks);

            if ($config->repositoryoption == 1) {
                $mform->addElement('select', 'plagiarism_compare_institution',
                                                get_string('compareinstitution', 'plagiarism_turnitin'), $options);
                $this->lock($mform, $location, $locks);
            }

            $mform->addElement('select', 'plagiarism_report_gen', get_string("reportgenspeed", "turnitintooltwo"), $genoptions);
            $this->lock($mform, $location, $locks);
            $mform->addElement('html', html_writer::tag('div', get_string('genspeednote', 'plagiarism_turnitin'), array('class' => 'tii_genspeednote')));
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
                $mform->addElement('text', 'plagiarism_locked_message', get_string("locked_message", "plagiarism_turnitin"), 'maxlength="50" size="50"' );
                $mform->setType('plagiarism_locked_message', PARAM_TEXT);
                $mform->setDefault('plagiarism_locked_message', get_string("locked_message_default", "plagiarism_turnitin") );
                $mform->addHelpButton('plagiarism_locked_message', 'locked_message', 'plagiarism_turnitin');
            }

            if ($location == "activity" && $config->usegrademark) {
                // Populate Rubric options.
                $rubricoptions = array('' => get_string('norubric', 'plagiarism_turnitin')) + $rubrics;
                if (!empty($currentrubric)) {
                    $rubricoptions[$currentrubric] = (isset($rubricoptions[$currentrubric])) ?
                                    $rubricoptions[$currentrubric] : get_string('otherrubric', 'plagiarism_turnitin');
                }

                $mform->addElement('select', 'plagiarism_rubric', get_string('attachrubric', 'plagiarism_turnitin'), $rubricoptions);

                $mform->addElement('static', 'rubric_link', '',
                                        html_writer::link($CFG->wwwroot.
                                                    '/mod/turnitintooltwo/extras.php?cmd=rubricmanager&view_context=box',
                                                    get_string('launchrubricmanager', 'plagiarism_turnitin'),
                                                    array('class' => 'rubric_manager_launch',
                                                        'title' => get_string('launchrubricmanager', 'plagiarism_turnitin'))).
                                                    html_writer::tag('span', '', array('class' => 'launch_form',
                                                                                    'id' => 'rubric_manager_form')));
                $mform->setDefault('plagiarism_rubric', '');

                $mform->addElement('hidden', 'rubric_warning_seen', '');
                $mform->setType('rubric_warning_seen', PARAM_RAW);

                $mform->addElement('static', 'rubric_note', '', get_string('attachrubricnote', 'plagiarism_turnitin'));
            } else {
                $mform->addElement('hidden', 'plagiarism_rubric', '');
                $mform->setType('plagiarism_rubric', PARAM_RAW);
            }

            if (!empty($config->useerater)) {
                $handbookoptions = array(
                                            1 => get_string('erater_handbook_advanced', 'plagiarism_turnitin'),
                                            2 => get_string('erater_handbook_highschool', 'plagiarism_turnitin'),
                                            3 => get_string('erater_handbook_middleschool', 'plagiarism_turnitin'),
                                            4 => get_string('erater_handbook_elementary', 'plagiarism_turnitin'),
                                            5 => get_string('erater_handbook_learners', 'plagiarism_turnitin')
                                        );

                $dictionaryoptions = array(
                                            'en_US' => get_string('erater_dictionary_enus', 'plagiarism_turnitin'),
                                            'en_GB' => get_string('erater_dictionary_engb', 'plagiarism_turnitin'),
                                            'en'    => get_string('erater_dictionary_en', 'plagiarism_turnitin')
                                        );
                $mform->addElement('select', 'plagiarism_erater', get_string('erater', 'plagiarism_turnitin'), $options);
                $mform->setDefault('plagiarism_erater', 0);

                $mform->addElement('select', 'plagiarism_erater_handbook', get_string('erater_handbook', 'plagiarism_turnitin'),
                                                $handbookoptions);
                $mform->setDefault('plagiarism_erater_handbook', 2);
                $mform->disabledIf('plagiarism_erater_handbook', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('select', 'plagiarism_erater_dictionary', get_string('erater_dictionary', 'plagiarism_turnitin'),
                                                $dictionaryoptions);
                $mform->setDefault('plagiarism_erater_dictionary', 'en_US');
                $mform->disabledIf('plagiarism_erater_dictionary', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_spelling', get_string('erater_categories', 'plagiarism_turnitin'),
                                                " ".get_string('erater_spelling', 'plagiarism_turnitin'));
                $mform->disabledIf('plagiarism_erater_spelling', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_grammar', '', " ".get_string('erater_grammar', 'plagiarism_turnitin'));
                $mform->disabledIf('plagiarism_erater_grammar', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_usage', '', " ".get_string('erater_usage', 'plagiarism_turnitin'));
                $mform->disabledIf('plagiarism_erater_usage', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_mechanics', '', " ".
                                                get_string('erater_mechanics', 'plagiarism_turnitin'));
                $mform->disabledIf('plagiarism_erater_mechanics', 'plagiarism_erater', 'eq', 0);

                $mform->addElement('checkbox', 'plagiarism_erater_style', '', " ".get_string('erater_style', 'plagiarism_turnitin'));
                $mform->disabledIf('plagiarism_erater_style', 'plagiarism_erater', 'eq', 0);
            }

            $mform->addElement('html', html_writer::tag('div', get_string('anonblindmarkingnote', 'plagiarism_turnitin'),
                                                                                array('class' => 'tii_anonblindmarkingnote')));

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

    public function show_file_errors_table($page = 0) {
        global $CFG, $OUTPUT;

        $limit = 100;
        $offset = $page * $limit;

        $plagiarismpluginturnitin = new plagiarism_plugin_turnitin();
        $filescount = $plagiarismpluginturnitin->get_file_upload_errors(0, 0, true);
        $files = $plagiarismpluginturnitin->get_file_upload_errors($offset, $limit);

        $baseurl = new moodle_url('/plagiarism/turnitin/settings.php', array('do' => 'errors'));
        $pagingbar = $OUTPUT->paging_bar($filescount, $page, $limit, $baseurl);

        // Do the table headers.
        $cells = array();
        $selectall = html_writer::checkbox('errors_select_all', false, false, '', array("class" => "select_all_checkbox"));
        $cells["checkbox"] = new html_table_cell($selectall);
        $cells["id"] = new html_table_cell(get_string('id', 'plagiarism_turnitin'));
        $cells["user"] = new html_table_cell(get_string('student', 'plagiarism_turnitin'));
        $cells["user"]->attributes['class'] = 'left';
        $cells["course"] = new html_table_cell(get_string('course', 'plagiarism_turnitin'));
        $cells["module"] = new html_table_cell(get_string('module', 'plagiarism_turnitin'));
        $cells["file"] = new html_table_cell(get_string('file'));
        $cells["error"] = new html_table_cell(get_string('error'));
        $cells["delete"] = new html_table_cell('&nbsp;');
        $cells["delete"]->attributes['class'] = 'centered_cell';

        $table = new html_table();
        $table->head = $cells;

        $i = 0;
        $rows = array();

        if (count($files) == 0) {
            $cells = array();
            $cells["checkbox"] = new html_table_cell(get_string('semptytable', 'plagiarism_turnitin'));
            $cells["checkbox"]->colspan = 8;
            $cells["checkbox"]->attributes['class'] = 'centered_cell';
            $rows[0] = new html_table_row($cells);
        } else {
            foreach ($files as $k => $v) {
                $cells = array();
                if (!empty($v->moduletype) && $v->moduletype != "forum") {

                    $cm = get_coursemodule_from_id($v->moduletype, $v->cm);

                    $checkbox = html_writer::checkbox('check_'.$k, $k, false, '', array("class" => "errors_checkbox"));
                    $cells["checkbox"] = new html_table_cell($checkbox);

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
                                                    $OUTPUT->pix_icon('fileicon', 'open '.$file->get_filename(), 'plagiarism_turnitin').
                                                        " ".$file->get_filename()));
                        } else {
                            $cells["file"] = get_string('filedoesnotexist', 'plagiarism_turnitin');
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
                        $errorstring = (is_null($errormsg)) ? get_string('ppsubmissionerrorseelogs', 'plagiarism_turnitin') : $errormsg;
                    } else {
                        $errorstring = get_string('errorcode'.$v->errorcode,
                                            'plagiarism_turnitin', display_size(TURNITINTOOLTWO_MAX_FILE_UPLOAD_SIZE));
                    }
                    $cells["error"] = $errorstring;

                    $fnd = array("\n", "\r");
                    $rep = array('\n', '\r');
                    $string = str_replace($fnd, $rep, get_string('deleteconfirm', 'plagiarism_turnitin'));

                    $attributes["onclick"] = "return confirm('".$string."');";
                    $cells["delete"] = new html_table_cell(html_writer::link($CFG->wwwroot.
                                            '/plagiarism/turnitin/settings.php?do=errors&action=deletefile&id='.$k,
                                            $OUTPUT->pix_icon('delete', get_string('deletesubmission', 'plagiarism_turnitin'),
                                                'plagiarism_turnitin'), $attributes));
                    $cells["delete"]->attributes['class'] = 'centered_cell';

                    $rows[$i] = new html_table_row($cells);
                    $i++;
                }
            }

            if ($i == 0) {
                $cells = array();
                $cells["checkbox"] = new html_table_cell(get_string('semptytable', 'plagiarism_turnitin'));
                $cells["checkbox"]->colspan = 8;
                $cells["checkbox"]->attributes['class'] = 'centered_cell';
                $rows[0] = new html_table_row($cells);
            } else {
                $table->id = "ppErrors";
            }
        }
        $table->data = $rows;
        $output = html_writer::table($table);

        return $pagingbar.$output.$pagingbar;
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

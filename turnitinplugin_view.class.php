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

        $elements[] = array('checkbox', 'turnitin_use', get_string('useturnitin', 'turnitintooltwo'));
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
    public function add_elements_to_settings_form($mform, $location = "activity", $cmid = 0) {
        global $CFG, $OUTPUT, $PAGE, $USER;

        $PAGE->requires->string_for_js('changerubricwarning', 'turnitintooltwo');
        $config = turnitintooltwo_admin_config();

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
        } else {
            $mform->addElement('header', 'plugin_header', get_string('turnitinpluginsettings', 'turnitintooltwo'));

            // Add in custom Javascript and CSS.
            $jsurl = new moodle_url('/mod/turnitintooltwo/scripts/jquery-1.8.2.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url('/mod/turnitintooltwo/scripts/turnitintooltwo.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url('/mod/turnitintooltwo/scripts/plagiarism_plugin.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url('/mod/turnitintooltwo/scripts/jquery.dataTables.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url('/mod/turnitintooltwo/scripts/jquery-ui-1.10.2.custom.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url('/mod/turnitintooltwo/scripts/jquery.colorbox-min.js');
            $PAGE->requires->js($jsurl, true);

            $cssurl = new moodle_url('/mod/turnitintooltwo/css/styles.css');
            $PAGE->requires->css($cssurl);
            $cssurl = new moodle_url('/mod/turnitintooltwo/css/styles_pp.css');
            $PAGE->requires->css($cssurl);
            $cssurl = new moodle_url('/mod/turnitintooltwo/css/colorbox.css');
            $PAGE->requires->css($cssurl);

            // Quickmark Manager.
            $quickmarkmanagerlink = $OUTPUT->box_start('row_quickmark_manager', '');
            $quickmarkmanagerlink .= html_writer::link($CFG->wwwroot.
                                            '/mod/turnitintooltwo/extras.php?cmd=quickmarkmanager&view_context=box',
                                            get_string('launchquickmarkmanager', 'turnitintooltwo'),
                                            array('class' => 'quickmark_manager_launch',
                                                'title' => get_string('launchquickmarkmanager', 'turnitintooltwo')));
            $quickmarkmanagerlink .= html_writer::tag('span', '',
                                            array('class' => 'launch_form', 'id' => 'quickmark_manager_form'));
            $quickmarkmanagerlink .= $OUTPUT->box_end(true);

            // Peermark Manager.
            $peermarkmanagerlink = '';
            if ($cmid != 0) {
                $peermarkmanagerlink .= $OUTPUT->box_start('row_peermark_manager', '');
                $peermarkmanagerlink .= html_writer::link($CFG->wwwroot.
                                                '/plagiarism/turnitin/ajax.php?cmid='.$cmid.
                                                    '&action=peermarkmanager&view_context=box',
                                                get_string('launchpeermarkmanager', 'turnitintooltwo'),
                                                array('class' => 'peermark_manager_pp_launch',
                                                        'id' => 'peermark_manager_'.$cmid,
                                                        'title' => get_string('launchpeermarkmanager', 'turnitintooltwo')));
                $peermarkmanagerlink .= html_writer::tag('span', '', array('class' => 'launch_form',
                                                                        'id' => 'peermark_manager_form'));
                $peermarkmanagerlink .= $OUTPUT->box_end(true);
            }

            $mform->addElement('static', 'static', '', $quickmarkmanagerlink.$peermarkmanagerlink);

        }
        $mform->addElement('select', 'use_turnitin', get_string("useturnitin", "turnitintooltwo"), $options);
        $mform->addElement('select', 'plagiarism_show_student_report', get_string("studentreports", "turnitintooltwo"), $options);
        $mform->addHelpButton('plagiarism_show_student_report', 'studentreports', 'turnitintooltwo');

        if ($mform->elementExists('submissiondrafts') || $location == 'defaults') {
            $tiidraftoptions = array(0 => get_string("submitondraft", "turnitintooltwo"), 
                                     1 => get_string("submitonfinal", "turnitintooltwo"));

            $mform->addElement('select', 'plagiarism_draft_submit', get_string("draftsubmit", "turnitintooltwo"), $tiidraftoptions);
            $mform->disabledIf('plagiarism_draft_submit', 'submissiondrafts', 'eq', 0);
        }

        $mform->addElement('select', 'plagiarism_allow_non_or_submissions', get_string("allownonor", "turnitintooltwo"), $options);
        $mform->addHelpButton('plagiarism_allow_non_or_submissions', 'allownonor', 'turnitintooltwo');

        $suboptions = array(0 => get_string('norepository', 'turnitintooltwo'),
                            1 => get_string('standardrepository', 'turnitintooltwo'));
        if ($config->userepository == "1") {
            $suboptions[2] = get_string('institutionalrepository', 'turnitintooltwo');
        }
        $mform->addElement('select', 'plagiarism_submitpapersto', get_string('submitpapersto', 'turnitintooltwo'), $suboptions);

        $mform->addElement('select', 'plagiarism_compare_student_papers', get_string("spapercheck", "turnitintooltwo"), $options);
        $mform->addElement('select', 'plagiarism_compare_internet', get_string("internetcheck", "turnitintooltwo"), $options);
        $mform->addElement('select', 'plagiarism_compare_journals', get_string("journalcheck", "turnitintooltwo"), $options);

        if ($config->userepository) {
            $mform->addElement('select', 'plagiarism_compare_institution',
                                            get_string('compareinstitution', 'turnitintooltwo'), $options);
        }

        $mform->addElement('select', 'plagiarism_report_gen', get_string("reportgenspeed", "turnitintooltwo"), $genoptions);
        $mform->addElement('select', 'plagiarism_exclude_biblio', get_string("excludebiblio", "turnitintooltwo"), $options);
        $mform->addElement('select', 'plagiarism_exclude_quoted', get_string("excludequoted", "turnitintooltwo"), $options);

        $mform->addElement('select', 'plagiarism_exclude_matches', get_string("excludevalue", "turnitintooltwo"),
                                                                            $excludetypeoptions);
        $mform->addElement('text', 'plagiarism_exclude_matches_value', '');
        $mform->setType('plagiarism_exclude_matches_value', PARAM_INT);
        $mform->addRule('plagiarism_exclude_matches_value', null, 'numeric', null, 'client');
        $mform->disabledIf('plagiarism_exclude_matches_value', 'plagiarism_exclude_matches', 'eq', 0);

        if ($location == "activity") {
            // Populate Rubric options.
            $rubricoptions = array('' => get_string('norubric', 'turnitintooltwo')) + $instructorrubrics;
            if (!empty($this->turnitintooltwo->rubric)) {
                $rubricoptions[$this->turnitintooltwo->rubric] = (isset($rubricoptions[$this->turnitintooltwo->rubric])) ?
                                $rubricoptions[$this->turnitintooltwo->rubric] : get_string('otherrubric', 'turnitintooltwo');
            }

            $rubricline = array();
            $rubricline[] = $mform->createElement('select', 'plagiarism_rubric', '', $rubricoptions);
            $rubricline[] = $mform->createElement('static', 'rubric_link', '',
                                    html_writer::link($CFG->wwwroot.
                                                '/mod/turnitintooltwo/extras.php?cmd=rubricmanager&view_context=box',
                                                get_string('launchrubricmanager', 'turnitintooltwo'),
                                                array('class' => 'rubric_manager_launch',
                                                    'title' => get_string('launchrubricmanager', 'turnitintooltwo'))).
                                                html_writer::tag('span', '', array('class' => 'launch_form',
                                                                                'id' => 'rubric_manager_form')));
            $mform->setDefault('rubric', '');
            $mform->addGroup($rubricline, 'rubricline', get_string('attachrubric', 'turnitintooltwo'), array(' '), false);
            $mform->addElement('hidden', 'rubric_warning_seen', '');
            $mform->setType('rubric_warning_seen', PARAM_RAW);

            $mform->addElement('static', 'rubric_note', '', get_string('attachrubricnote', 'turnitintooltwo'));
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

        $mform->addElement('select', 'plagiarism_anonymity', get_string("turnitinanon", "turnitintooltwo"), $options);
        $mform->addElement('select', 'plagiarism_transmatch', get_string("transmatch", "turnitintooltwo"), $options);
        $mform->addElement('hidden', 'action', "defaults");
        $mform->setType('action', PARAM_RAW);

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
                        $file = $fs->get_file_by_hash($v->identifier);
                        $cells["file"] = new html_table_cell(html_writer::link($CFG->wwwroot.'/pluginfile.php/'.
                                                $file->get_contextid().'/'.$file->get_component().'/'.$file->get_filearea().'/'.
                                                $file->get_itemid().'/'.$file->get_filename(),
                                                $OUTPUT->pix_icon('fileicon', 'open '.$file->get_filename(), 'mod_turnitintooltwo').
                                                    " ".$file->get_filename()));
                    } else {
                        $cells["file"] = str_replace('_', ' ', ucfirst($v->submissiontype));
                    }

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
}

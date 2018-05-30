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

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/lib.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__."/turnitinplugin_view.class.php");

$turnitinpluginview = new turnitinplugin_view();

require_login();
admin_externalpage_setup('plagiarismturnitin');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$do = optional_param('do', "config", PARAM_ALPHA);
$action = optional_param('action', "", PARAM_ALPHA);

if (isset($_SESSION["notice"])) {
    $notice = $_SESSION["notice"];
    $notice["type"] = (empty($_SESSION["notice"]["type"])) ? "general" : $_SESSION["notice"]["type"];
    unset($_SESSION["notice"]);
} else {
    $notice = null;
}

$plagiarismpluginturnitin = new plagiarism_plugin_turnitin();
$supportedmods = array('assign', 'forum', 'workshop');

if ($DB->record_exists('modules', array('name' => 'coursework', 'visible' => 1))) {
    $supportedmods[] = 'coursework';
}

// Get plugin config.
$pluginconfig = array();
$pluginconfig['turnitin_use'] = get_config('plagiarism', 'turnitin_use');

// Check that mod enabled setting has been initialised.
foreach ($supportedmods as $mod) {
    $pluginconfig['turnitin_use_mod_'.$mod] = $plagiarismpluginturnitin->get_config_settings('mod_'.$mod);
}

$plugindefaults = $plagiarismpluginturnitin->get_settings();

// Save Settings.
if (!empty($action)) {
    switch ($action) {
        case "config":
            // Overall plugin use setting.
            $turnitinoveralluse = optional_param('turnitin_use', 0, PARAM_INT);
            set_config('turnitin_use', $turnitinoveralluse, 'plagiarism');

            // Allow Turnitin to be on for Individual modules.
            foreach ($supportedmods as $mod) {
                $turnitinuse = optional_param('turnitin_use_mod_'.$mod, 0, PARAM_INT);
                $turnitinuse = ($turnitinoveralluse == 0) ? 0 : $turnitinuse;

                set_config('turnitin_use_mod_'.$mod, $turnitinuse, 'plagiarism');
            }

            $_SESSION['notice']['message'] = get_string('configupdated', 'plagiarism_turnitin');
            redirect(new moodle_url('/plagiarism/turnitin/settings.php'));
            exit;
            break;

        case "defaults":
            $fields = $plagiarismpluginturnitin->get_settings_fields();

            $settingsfields = array();
            foreach ($fields as $field) {
                array_push($settingsfields, $field);
                array_push($settingsfields, $field . '_lock');
            }
            array_push($settingsfields, 'plagiarism_locked_message');

            foreach ($settingsfields as $field) {
                $defaultfield = new stdClass();
                $defaultfield->cm = null;
                $defaultfield->name = $field;
                if ($field == 'plagiarism_locked_message') {
                    $defaultfield->value = optional_param($field, '', PARAM_TEXT);
                } else {
                    $defaultfield->value = optional_param($field, '', PARAM_ALPHANUMEXT);
                }

                if (isset($plugindefaults[$field])) {
                    $defaultfield->id = $DB->get_field('plagiarism_turnitin_config', 'id',
                                                (array('cm' => null, 'name' => $field)));
                    if (!$DB->update_record('plagiarism_turnitin_config', $defaultfield)) {
                        turnitintooltwo_print_error('defaultupdateerror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
                    }
                } else {
                    $defaultfield->config_hash = $defaultfield->cm."_".$defaultfield->name;
                    if (!$DB->insert_record('plagiarism_turnitin_config', $defaultfield)) {
                        turnitintooltwo_print_error('defaultinserterror', 'plagiarism_turnitin', null, null, __FILE__, __LINE__);
                    }
                }
            }

            $_SESSION['notice']['message'] = get_string('defaultupdated', 'plagiarism_turnitin');
            redirect(new moodle_url('/plagiarism/turnitin/settings.php', array('do' => 'defaults')));
            exit;
            break;

        case "deletefile":
            $id = optional_param('id', 0, PARAM_INT);
            $DB->update_record('plagiarism_turnitin_files', array('id' => $id, 'statuscode' => "deleted"));
            redirect(new moodle_url('/plagiarism/turnitin/settings.php', array('do' => 'errors')));
            exit;
            break;
    }
}

// Include Javascript & CSS.
if ($do == "errors") {
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('plagiarism-turnitin_settings', 'plagiarism_turnitin');
}

if ($do != "savereport") {
    echo $OUTPUT->header();
}

switch ($do) {
    case "config":
        $turnitinpluginview->draw_settings_tab_menu('turnitinsettings', $notice);

        require_once(__DIR__ . '/classes/forms/tiisetupform.class.php');

        $tiisetupform = new tiisetupform();
        $tiisetupform->set_data($pluginconfig);

        echo $tiisetupform->display();

        break;

    case "defaults":
        $turnitinpluginview->draw_settings_tab_menu('turnitindefaults', $notice);

        $mform = new turnitin_plagiarism_plugin_form($CFG->wwwroot.'/plagiarism/turnitin/settings.php?do=defaults');
        $mform->set_data($plugindefaults);
        $mform->display();
        break;

    case "viewreport":
    case "savereport":
        $output = '';
        if ($do == 'viewreport') {

            $activetab = ($do == "savereport") ? "turnitinsaveusage" : "turnitinshowusage";
            $turnitinpluginview->draw_settings_tab_menu($activetab, $notice);

            $output .= "<pre>";
            $output .= "====== Turnitin Plagiarism Plugin Data Dump Output ======\r\n\r\n";

        } else if ($do == 'savereport') {

            $filename = 'tii_pp_datadump_'.gmdate('dmYhm', time()).'.txt';
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

            $output .= "====== Turnitin Plagiarism Plugin Data Dump File ======\r\n\r\n";
        }

        $tables = array('plagiarism_turnitin_config', 'plagiarism_turnitin_files');

        foreach ($tables as $table) {

            $output .= "== ".$table." ==\r\n\r\n";

            if ($data = $DB->get_records($table)) {

                $headers = array_keys(get_object_vars(current($data)));
                $columnwidth = 25;
                if ($table == 'plagiarism_turnitin_config') {
                    $columnwidth = 32;
                }

                $output .= str_pad('', (($columnwidth + 2) * count($headers)) + 1, "=");
                if ($table == 'plagiarism_turnitin_files') {
                    $output .= str_pad('', $columnwidth + 2, "=");
                }
                $output .= "\r\n";

                $output .= "|";
                foreach ($headers as $header) {
                    $output .= ' '.str_pad($header, $columnwidth, " ", 1).'|';
                }
                $output .= "\r\n";

                $output .= str_pad('', (($columnwidth + 2) * count($headers)) + 1, "=");
                if ($table == 'plagiarism_turnitin_files') {
                    $output .= str_pad('', $columnwidth + 2, "=");
                }
                $output .= "\r\n";

                foreach ($data as $datarow) {
                    $datarow = get_object_vars($datarow);
                    $output .= "|";
                    foreach ($datarow as $datacell) {
                        $output .= ' '.htmlspecialchars(str_pad(substr($datacell, 0, $columnwidth), $columnwidth, " ", 1)).'|';
                    }
                    $output .= "\r\n";
                }
                $output .= str_pad('', (($columnwidth + 2) * count($headers)) + 1, "-");
                if ($table == 'plagiarism_turnitin_files') {
                    $output .= str_pad('', $columnwidth + 2, "-");
                }
                $output .= "\r\n\r\n";
            } else {
                $output .= get_string('notavailableyet', 'plagiarism_turnitin')."\r\n";
            }

        }

        if ($do == 'viewreport') {
            $output .= "</pre>";
            echo $output;
        } else if ($do == 'savereport') {
            echo $output;
            exit;
        }
        break;

    case "errors":
        $page = optional_param('page', 0, PARAM_INT);
        $resubmitted = optional_param('resubmitted', '', PARAM_ALPHA);
        $turnitinpluginview->draw_settings_tab_menu('turnitinerrors', $notice);
        echo html_writer::tag("p", get_string('pperrorsdesc', 'plagiarism_turnitin'));

        if ($resubmitted == "success") {
            echo html_writer::tag("div", get_string('pperrorssuccess', 'plagiarism_turnitin'),
                                            array('class' => 'pp_errors_success'));
        } else if ($resubmitted == "errors") {
            echo html_writer::tag("div", get_string('pperrorsfail', 'plagiarism_turnitin'),
                                            array('class' => 'pp_errors_warning'));
        }

        echo html_writer::tag("button", get_string('resubmitselected', 'plagiarism_turnitin'),
                                array("class" => "btn btn-primary pp-resubmit-files", "disabled" => "disabled"));

        echo $turnitinpluginview->show_file_errors_table($page);

        echo html_writer::tag("button", get_string('resubmitselected', 'plagiarism_turnitin'),
                                array("class" => "btn btn-primary pp-resubmit-files", "disabled" => "disabled"));
        break;
}

echo $OUTPUT->footer();
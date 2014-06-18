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

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/lib.php');
require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');

require_once("turnitinplugin_view.class.php");
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
$supported_mods = array('assign', 'forum', 'workshop');
$pluginconfig = array();
foreach ($supported_mods as $mod) {
    $tmp_pluginconfig = $plagiarismpluginturnitin->get_config_settings('mod_'.$mod);
    $pluginconfig = array_merge($pluginconfig, $tmp_pluginconfig);
}
$plugindefaults = $plagiarismpluginturnitin->get_settings();

// Save Settings.
if (!empty($action)) {
    switch ($action) {
        case "config":
            foreach ($supported_mods as $mod) {
                $turnitinuse = optional_param('turnitin_use_mod_'.$mod, 0, PARAM_INT);
                if ($configfield = $DB->get_record('config_plugins', array('name' => 'turnitin_use_mod_'.$mod, 'plugin' => 'plagiarism'))) {
                    $configfield->value = $turnitinuse;
                    if (! $DB->update_record('config_plugins', $configfield)) {
                        turnitintooltwo_print_error('settingsupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    }
                } else {
                    $configfield = new object();
                    $configfield->value = $turnitinuse;
                    $configfield->plugin = 'plagiarism';
                    $configfield->name = 'turnitin_use_mod_'.$mod;
                    if (! $DB->insert_record('config_plugins', $configfield)) {
                        turnitintooltwo_print_error('settingsinserterror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    }
                }
            }

            $_SESSION['notice']['message'] = get_string('configupdated', 'turnitintooltwo');
            header('Location: '.$CFG->wwwroot.'/plagiarism/turnitin/settings.php');
            exit;
            break;

        case "defaults":
            $settingsfields = $plagiarismpluginturnitin->get_settings_fields();

            foreach ($settingsfields as $field) {
                $defaultfield = new object();
                $defaultfield->cm = 0;
                $defaultfield->name = $field;
                $defaultfield->value = optional_param($field, '', PARAM_ALPHANUMEXT);

                if (isset($plugindefaults[$field])) {
                    $defaultfield->id = $DB->get_field('plagiarism_turnitin_config', 'id',
                                                (array('cm' => 0, 'name' => $field)));
                    if (!$DB->update_record('plagiarism_turnitin_config', $defaultfield)) {
                        turnitintooltwo_print_error('defaultupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    }
                } else {
                    if (!$DB->insert_record('plagiarism_turnitin_config', $defaultfield)) {
                        turnitintooltwo_print_error('defaultinserterror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    }
                }
            }

            $_SESSION['notice']['message'] = get_string('defaultupdated', 'turnitintooltwo');
            header('Location: '.$CFG->wwwroot.'/plagiarism/turnitin/settings.php?do=defaults');
            exit;
            break;

        case "deletefile":
            $id = optional_param('id', 0, PARAM_INT);
            $DB->delete_records('plagiarism_turnitin_files', array('id' => $id));

            header('Location: '.$CFG->wwwroot.'/plagiarism/turnitin/settings.php?do=errors');
            exit;
            break;
    }
}

echo $OUTPUT->header();

echo html_writer::tag('link', '', array("rel" => "stylesheet", "type" => "text/css",
                                                            "href" => $CFG->wwwroot."/mod/turnitintooltwo/css/styles_pp.css"));

switch ($do) {
    case "config":
        $turnitinpluginview->draw_settings_tab_menu('turnitinsettings', $notice);

        echo $turnitinpluginview->show_config_form($pluginconfig);
        break;

    case "defaults":
        $turnitinpluginview->draw_settings_tab_menu('turnitindefaults', $notice);

        $mform = new turnitin_plagiarism_plugin_form($CFG->wwwroot.'/plagiarism/turnitin/settings.php?do=defaults');
        $mform->set_data($plugindefaults);
        $mform->display();
        break;

    case "errors":
        $turnitinpluginview->draw_settings_tab_menu('turnitinerrors', $notice);
        echo html_writer::tag("p", get_string('errorsdesc', 'turnitintooltwo'));
        echo $turnitinpluginview->show_file_errors_table();
        break;
}

echo $OUTPUT->footer();
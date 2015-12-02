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
 * @package   turnitintooltwo
 * @copyright 2012 iParadigms LLC
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once(__DIR__."/lib.php");

require_once($CFG->dirroot."/mod/turnitintooltwo/lib.php");
require_once($CFG->dirroot."/mod/turnitintooltwo/turnitintooltwo_view.class.php");

$turnitintooltwoview = new turnitintooltwo_view();

$cmd = optional_param('cmd', "", PARAM_ALPHAEXT);
$viewcontext = optional_param('view_context', "window", PARAM_ALPHAEXT);

// If opening DV then $viewcontext needs to be set to box
if ($cmd == "origreport" || $cmd == "grademark") {
    $viewcontext = "box";
}

// Initialise variables.
$output = "";
$jsrequired = false;

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('', $cmid);
$context = context_course::instance($cm->course);

// Work out user role.
switch ($cm->modname) {
    case "forum":
    case "workshop":
        $userrole = (has_capability('plagiarism/turnitin:viewfullreport', $context)) ? 'Instructor' : 'Learner';
        break;
    default:
        $userrole = (has_capability('mod/'.$cm->modname.':grade', $context)) ? 'Instructor' : 'Learner';
        break;
}

$PAGE->set_context(context_system::instance());
require_login();

switch ($cmd) {
    case "origreport":
    case "grademark":
        $submissionid = required_param('submissionid', PARAM_INT);
        $user = new turnitintooltwo_user($USER->id, $userrole);
        $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP');

        if ($userrole == 'Instructor') {
            $user->join_user_to_class($coursedata->turnitin_cid);
        }

        // Edit assignment in Turnitin in case any changes have been made that would affect DV.
        $pluginturnitin = new plagiarism_plugin_turnitin();
        $syncassignment = $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid);

        if ($syncassignment['success']) {
            echo html_writer::tag("div", $turnitintooltwoview->output_dv_launch_form($cmd, $submissionid, $user->tii_user_id, $userrole, ''),
                                                                                array("class" => "launch_form"));
            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }

        exit;
        break;

    case "useragreement":
        $PAGE->set_pagelayout('embedded');
        if ($CFG->branch <= 25) {
            $jsurl = new moodle_url($CFG->wwwroot.'/plagiarism/turnitin/jquery/jquery-1.8.2.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url($CFG->wwwroot.'/plagiarism/turnitin/jquery/jquery-ui-1.10.4.custom.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url($CFG->wwwroot.'/plagiarism/turnitin/jquery/turnitin_module.js');
            $PAGE->requires->js($jsurl);
        } else {
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('plagiarism-turnitin_module', 'plagiarism_turnitin');
        }
        $user = new turnitintooltwo_user($USER->id, "Learner");

        $output .= $OUTPUT->box_start('tii_eula_launch');
        $output .= turnitintooltwo_view::output_dv_launch_form("useragreement", 0, $user->tii_user_id, "Learner", '');
        $output .= $OUTPUT->box_end(true);
        echo $output;

        echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        exit;
    	break;
}

// Build page.
echo $turnitintooltwoview->output_header(null,
            null,
            $_SERVER["REQUEST_URI"],
            '',
            '',
            array(),
            "",
            "",
            true,
            '',
            '');

echo html_writer::tag("div", $viewcontext, array("id" => "tii_view_context"));

echo $output;

echo $OUTPUT->footer();

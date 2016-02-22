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

// Initialise variables.
$output = "";
$jsrequired = false;

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('', $cmid);
$context = context_course::instance($cm->course);

$PAGE->set_context(context_system::instance());
require_login();

switch ($cmd) {
    case "useragreement":
        $PAGE->set_pagelayout('embedded');

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('plagiarism-turnitin_module', 'plagiarism_turnitin');

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

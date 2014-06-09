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

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once("lib.php");

require_once($CFG->dirroot."/mod/turnitintooltwo/lib.php");
require_once($CFG->dirroot."/mod/turnitintooltwo/turnitintooltwo_view.class.php");

$turnitintooltwoview = new turnitintooltwo_view();

$cmd = optional_param('cmd', "", PARAM_ALPHAEXT);
$viewcontext = optional_param('view_context', "window", PARAM_ALPHAEXT);

// Initialise variables.
$output = "";
$jsrequired = false;

$cmid = required_param('cmid', PARAM_INT);

$PAGE->set_context(context_system::instance());
require_login();

// Load Javascript and CSS.
$turnitintooltwoview->load_page_components(false);

switch ($cmd) {
    case "useragreement":
		$cssurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/css/styles_pp.css');
        $PAGE->requires->css($cssurl);
    	$jsurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/scripts/plagiarism_plugin.js');
        $PAGE->requires->js($jsurl);

        $output .= html_writer::tag('span', $cmid, array('class' => 'cmid'));
    	$user = new turnitintooltwo_user($USER->id, "Learner");

    	$turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

    	$output .= html_writer::tag("div",
                        turnitintooltwo_view::output_dv_launch_form("useragreement", 0, $user->tii_user_id, "Learner",
                        								get_string('turnitinula', 'turnitintooltwo'), false),
                            								array("class" => "eula_launch_form"));
    	break;
}

// Build page.
echo $turnitintooltwoview->output_header(null,
            null,
            $_SERVER["REQUEST_URI"],
            $title,
            $title,
            $nav,
            "",
            "",
            true,
            '',
            '');

echo html_writer::tag("div", $viewcontext, array("id" => "view_context"));
if ($cmd == 'courses') {
    echo $OUTPUT->heading(get_string('pluginname', 'turnitintooltwo'), 2, 'main');
    // Show a warning if javascript is not enabled while a tutor is logged in.
    echo html_writer::tag('noscript', get_string('noscript', 'turnitintooltwo'), array("class" => "warning"));
}

echo $output;

echo $OUTPUT->footer();

?>
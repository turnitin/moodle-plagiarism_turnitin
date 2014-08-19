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

switch ($cmd) {
    case "useragreement":
        $cssurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/css/styles_pp.css');
        $PAGE->requires->css($cssurl);
        if ($CFG->branch <= 25) {
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery-1.8.2.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url('/mod/turnitintooltwo/jquery/jquery-ui-1.10.4.custom.min.js');
            $PAGE->requires->js($jsurl, true);
            $jsurl = new moodle_url($CFG->wwwroot.'/mod/turnitintooltwo/jquery/plagiarism_plugin.js');
            $PAGE->requires->js($jsurl);
        } else {
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('turnitintooltwo-plagiarism_plugin', 'mod_turnitintooltwo');
        }

        $output .= $OUTPUT->box_start('tii_links_container');

        $output .= html_writer::tag('span', $cmid, array('class' => 'cmid'));
    	$user = new turnitintooltwo_user($USER->id, "Learner");

     	$turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();
        $loadericon = $OUTPUT->pix_icon('loader-lrg', get_string('redirecttoeula', 'turnitintooltwo'), 'mod_turnitintooltwo');
        $text = html_writer::tag('p', get_string('redirecttoeula', 'turnitintooltwo'));

        $output .= html_writer::tag('div', $loadericon.$text, array('class' => 'eularedirect clear'));
    	$output .= html_writer::tag("div",
                        turnitintooltwo_view::output_dv_launch_form("useragreement", 0, $user->tii_user_id, "Learner",
                        								get_string('turnitinula', 'turnitintooltwo'), false, 'PP'),
                            								array("class" => "eula_launch_form hide"));

        $output .= $OUTPUT->box_end(true);
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

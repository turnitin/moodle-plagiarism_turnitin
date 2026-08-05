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
 * Turnitin extras page
 *
 * @package   plagiarism_turnitin
 * @copyright 2012 iParadigms LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');


$turnitinview = new \plagiarism_turnitin\turnitin_view();

$cmd = optional_param('cmd', "", PARAM_ALPHAEXT);
$viewcontext = optional_param('view_context', "window", PARAM_ALPHAEXT);

// Initialise variables.
$output = "";
$jsrequired = false;

$cmid = optional_param('cmid', 0, PARAM_INT);

if ($cmid) {
    $cm = get_coursemodule_from_id('', $cmid);
    $context = context_course::instance($cm->course);
}

$PAGE->set_context(context_system::instance());
require_login();

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');

switch ($cmd) {
    case "rubricmanager":
        $PAGE->set_pagelayout('embedded');
        $courseid = optional_param('courseid', 0, PARAM_INT);
        echo \plagiarism_turnitin\turnitin_extras_handler::render_rubric_manager($courseid);
        break;

    case "quickmarkmanager":
        $PAGE->set_pagelayout('embedded');
        echo \plagiarism_turnitin\turnitin_extras_handler::render_quickmark_manager();
        break;

    case "useragreement":
        $PAGE->set_pagelayout('embedded');
        echo \plagiarism_turnitin\turnitin_extras_handler::render_user_agreement($USER->id);
        exit;
        break;
}

// Build page.
echo $turnitinview->output_header(qualified_me());

echo html_writer::tag("div", $viewcontext, ["id" => "tii_view_context"]);

echo $output;

echo $OUTPUT->footer();

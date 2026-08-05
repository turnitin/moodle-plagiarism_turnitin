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
 * Turnitin ajax file
 *
 * @package   plagiarism_turnitin
 * @copyright 2013 iParadigms LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Integrations\PhpSdk\TiiClass;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      \core\session\manager::write_close();
}

$action = required_param('action', PARAM_ALPHAEXT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$itemid = optional_param('itemid', 0, PARAM_INT);
if (!empty($cmid)) {
    $cm = get_coursemodule_from_id('', $cmid);
    $context = context_course::instance($cm->course);

    // Work out user role.
    $userrole = '';
    switch ($cm->modname) {
        case "forum":
        case "workshop":
            $userrole = (has_capability('plagiarism/turnitin:viewfullreport', $context)) ? 'Instructor' : 'Learner';
            break;
        default:
            $userrole = (has_capability('mod/' . $cm->modname . ':grade', $context)) ? 'Instructor' : 'Learner';
            break;
    }
}

$pathnamehash = optional_param('pathnamehash', "", PARAM_ALPHANUM);
$submissiontype = optional_param('submission_type', "", PARAM_ALPHAEXT);
$return = [];

// Initialise plugin class.
$pluginturnitin = new plagiarism_plugin_turnitin();

switch ($action) {
    case "get_dv_html":
        $submissionid = required_param('submissionid', PARAM_INT);
        $dvtype = optional_param('dvtype', 'default', PARAM_ALPHAEXT);
        $user = new \plagiarism_turnitin\turnitin_user($USER->id, $userrole);
        $coursedata = \plagiarism_turnitin\turnitin_assignment::get_course_data($cm->course);

        if ($userrole == 'Instructor') {
            $user->join_user_to_class($coursedata->turnitin_cid);
        }

        // Update course data in Turnitin.
        $turnitinassignment = new \plagiarism_turnitin\turnitin_assignment(0);
        $turnitinassignment->edit_tii_course($coursedata);

        // Edit assignment in Turnitin in case any changes have been made that would affect DV.
        $pluginturnitin = new plagiarism_plugin_turnitin();
        $syncassignment = $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid);

        if ($syncassignment['success']) {
            $return = html_writer::tag(
                "div",
                \plagiarism_turnitin\turnitin_view::output_launch_form(
                    $dvtype,
                    $submissionid,
                    $user->tiiuserid,
                    $userrole,
                    ''
                ),
                ['style' => 'display: none']
            );
        }
        break;

    case "update_grade":
        if (!confirm_sesskey()) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }

        include_once($CFG->libdir . "/gradelib.php");

        $submissionid = optional_param('submission', 0, PARAM_INT);

        if ($userrole == 'Instructor') {
            $pluginturnitin->update_rubric_from_tii($cm);
            $return["status"] = $pluginturnitin->update_grades_from_tii($cm);
            \plagiarism_turnitin\turnitin_ajax_handler::record_grade_sync_timestamp($cm->id);
        } else {
            $return["status"] = $pluginturnitin->update_grade_from_tii($cm, $submissionid);
        }
        break;

    case "refresh_peermark_assignments":
        if (!confirm_sesskey()) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }

        $tiiassignment = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->id, 'name' => 'turnitin_assignid']);
        $pluginturnitin->refresh_peermark_assignments($cm, $tiiassignment->value);
        break;

    case "peermarkmanager":
        if ($userrole == 'Instructor') {
            $plagiarismpluginturnitin = new plagiarism_plugin_turnitin();
            $coursedata = \plagiarism_turnitin\turnitin_course::get_course_data(
                $cm->id,
                $cm->course,
                'site',
                $plagiarismpluginturnitin
            );

            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->id, 'name' => 'turnitin_assignid']);

            if ($tiiassignment) {
                $tiiassignmentid = $tiiassignment->value;
            } else {
                // Create the module as an assignment in Turnitin.
                $tiiassignment = $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid);
                $tiiassignmentid = $tiiassignment['tiiassignmentid'];
            }

            $user = new \plagiarism_turnitin\turnitin_user($USER->id, "Instructor");
            $user->join_user_to_class($coursedata->turnitin_cid);

            echo html_writer::tag(
                'div',
                \plagiarism_turnitin\turnitin_view::output_lti_form_launch('peermark_manager', 'Instructor', $tiiassignmentid),
                [
                    'class' => 'launch_form',
                    'style' => 'display:none;',
                ]
            );

            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }
        break;

    case "rubricview":
        if (is_enrolled($context)) {
            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', [ 'cm' => $cm->id, 'name' => 'turnitin_assignid' ]);

            $user = new \plagiarism_turnitin\turnitin_user($USER->id, "Learner");
            $coursedata = \plagiarism_turnitin\turnitin_assignment::get_course_data($cm->course);
            $user->join_user_to_class($coursedata->turnitin_cid);

            echo html_writer::tag(
                'div',
                \plagiarism_turnitin\turnitin_view::output_lti_form_launch('rubric_view', 'Learner', $tiiassignment->value),
                [
                    'class' => 'launch_form',
                    'style' => 'display:none;',
                ]
            );

            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }
        break;

    case "peermarkreviews":
        $replypost = 'mod/' . $cm->modname . ':replypost';
        $submit = 'mod/' . $cm->modname . ':submit';
        $isstudent = ($cm->modname == "forum") ? has_capability($replypost, $context) : has_capability($submit, $context);

        if ($userrole == 'Instructor' || $isstudent) {
            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->id, 'name' => 'turnitin_assignid']);

            $user = new \plagiarism_turnitin\turnitin_user($USER->id, $userrole);
            $coursedata = \plagiarism_turnitin\turnitin_assignment::get_course_data($cm->course);
            $user->join_user_to_class($coursedata->turnitin_cid);

            echo html_writer::tag(
                'div',
                \plagiarism_turnitin\turnitin_view::output_lti_form_launch('peermark_reviews', $userrole, $tiiassignment->value),
                [
                    'class' => 'launch_form',
                    'style' => 'display:none;',
                ]
            );

            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }
        break;

    case "actionuseragreement":
        if (!confirm_sesskey()) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $message = optional_param('message', '', PARAM_ALPHAEXT);
        \plagiarism_turnitin\turnitin_ajax_handler::action_user_agreement($USER->id, $message);
        break;

    case "resubmit_event":
        if (!confirm_sesskey()) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $forumdata    = optional_param('forumdata', '', PARAM_ALPHANUMEXT);
        $forumpost    = optional_param('forumpost', '', PARAM_BASE64);
        $submissionid = required_param('submissionid', PARAM_INT);
        if (\plagiarism_turnitin\turnitin_ajax_handler::resubmit_event($submissionid, $forumdata, $forumpost)) {
            $return = ['success' => true];
        }
        break;

    case "resubmit_events":
        if (!confirm_sesskey()) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $submissionids = optional_param_array('submission_ids', [], PARAM_INT);
        $return        = \plagiarism_turnitin\turnitin_ajax_handler::resubmit_events($submissionids);
        break;

    case "test_connection":
        if (!confirm_sesskey()) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $PAGE->set_context(context_system::instance());
        if (is_siteadmin()) {
            $accountid     = required_param('accountid', PARAM_RAW);
            $accountshared = required_param('accountshared', PARAM_RAW);
            $url           = required_param('url', PARAM_RAW);
            $data          = \plagiarism_turnitin\turnitin_ajax_handler::test_connection(
                $accountid,
                $accountshared,
                $url
            );
        } else {
            $data = [
                'connection_status' => 'fail',
                'msg' => get_string('connecttestcommerror', 'plagiarism_turnitin'),
            ];
        }
        echo json_encode($data);
        break;

    case "get_users":
        $PAGE->set_context(context_system::instance());
        if (is_siteadmin()) {
            header('Content-type: application/json; charset=utf-8');
            echo json_encode(\turnitin_user::plagiarism_turnitin_getusers());
        } else {
            throw new \moodle_exception('accessdenied', 'admin');
        }
        break;

    case "refresh_rubric_select":
        $courseid = required_param('course', PARAM_INT);
        $assignmentid = required_param('assignment', PARAM_INT);
        $modulename = required_param('modulename', PARAM_ALPHA);

        $PAGE->set_context(context_course::instance($courseid));

        if (has_capability('moodle/course:update', context_course::instance($courseid))) {
            // Set Rubric options to instructor rubrics.
            $instructor = new \plagiarism_turnitin\turnitin_user($USER->id, 'Instructor');
            $instructor->set_user_values_from_tii();
            $instructorrubrics = $instructor->get_instructor_rubrics();

            $options = [0 => get_string('norubric', 'plagiarism_turnitin')] + $instructorrubrics;

            // Get rubrics that are shared on the Turnitin account.
            $turnitinclass = new \plagiarism_turnitin\turnitin_class($courseid);

            $turnitinclass->read_class_from_tii();
            $sharedrubrics = $turnitinclass->sharedrubrics;

            foreach ($sharedrubrics as $group => $grouprubrics) {
                foreach ($grouprubrics as $rubricid => $rubricname) {
                    $options[$group][$rubricid] = $rubricname;
                }
            }

            // Get assignment details.
            if (!empty($assignmentid)) {
                $cm = get_coursemodule_from_instance($modulename, $assignmentid);
                $plagiarismsettings = \plagiarism_turnitin\turnitin_settings::for_cm($cm->id);
            }

            // Add in selected rubric if it belongs to another instructor.
            if (!empty($assignmentid)) {
                if (!empty($plagiarismsettings["plagiarism_rubric"])) {
                    if (isset($options[$plagiarismsettings["plagiarism_rubric"]])) {
                        $rubricname = $options[$plagiarismsettings["plagiarism_rubric"]];
                    } else {
                        $rubricname = get_string('otherrubric', 'plagiarism_turnitin');
                    }
                    $options[$plagiarismsettings["plagiarism_rubric"]] = $rubricname;
                }
            }
        } else {
            $options = [];
        }

        echo json_encode($options);
        break;
}

if (!empty($return)) {
    echo json_encode($return);
}

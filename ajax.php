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

require_once(__DIR__."/../../config.php");
require_once(__DIR__."/lib.php");
require_once($CFG->dirroot.'/mod/turnitintooltwo/turnitintooltwo_assignment.class.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/turnitintooltwo_view.class.php');

require_login();

$action = required_param('action', PARAM_ALPHAEXT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$itemid = optional_param('itemid', 0, PARAM_INT);
if( !empty( $cmid ) ){
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
            $userrole = (has_capability('mod/'.$cm->modname.':grade', $context)) ? 'Instructor' : 'Learner';
            break;
    }
}

$pathnamehash = optional_param('pathnamehash', "", PARAM_ALPHANUM);
$submissiontype = optional_param('submission_type', "", PARAM_ALPHAEXT);
$return = array();

// Initialise plugin class.
$pluginturnitin = new plagiarism_plugin_turnitin();

switch ($action) {
    case "get_dv_html":
        $submissionid = required_param('submissionid', PARAM_INT);
        $dvtype = optional_param('dvtype', 'default', PARAM_ALPHAEXT);
        $user = new turnitintooltwo_user($USER->id, $userrole);
        $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP');

        if ($userrole == 'Instructor') {
            $user->join_user_to_class($coursedata->turnitin_cid);
        }

        // Edit assignment in Turnitin in case any changes have been made that would affect DV.
        $pluginturnitin = new plagiarism_plugin_turnitin();
        $syncassignment = $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid);

        if ($syncassignment['success']) {
            $turnitintooltwoview = new turnitintooltwo_view();
            $return = html_writer::tag("div",
                                        $turnitintooltwoview->output_dv_launch_form($dvtype, $submissionid, $user->tii_user_id,
                                                                    $userrole, ''), array('style' => 'display: none'));
        }
        break;

    case "update_grade":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        include_once($CFG->libdir."/gradelib.php");

        $submissionid = optional_param('submission', 0, PARAM_INT);

        if ($userrole == 'Instructor' && $cm->modname == "assign") {
            $return["status"] = $pluginturnitin->update_grades_from_tii($cm);

            $moduleconfigvalue = new stdClass();

            // If we have a turnitin timestamp stored then update it, otherwise create it.
            if ($timestampid = $DB->get_record('plagiarism_turnitin_config',
                                        array('cm' => $cm->id, 'name' => 'grades_last_synced'), 'id')) {
                $moduleconfigvalue->id = $timestampid->id;
                $moduleconfigvalue->value = time();
                $DB->update_record('plagiarism_turnitin_config', $moduleconfigvalue);
            } else {
                $moduleconfigvalue->cm = $cm->id;
                $moduleconfigvalue->name = 'grades_last_synced';
                $moduleconfigvalue->value = time();
                $DB->insert_record('plagiarism_turnitin_config', $moduleconfigvalue);
            }

        } else {
            $return["status"] = $pluginturnitin->update_grade_from_tii($cm, $submissionid);
        }
        break;

    case "refresh_peermark_assignments":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        $tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cm->id, 'name' => 'turnitin_assignid'));
        $pluginturnitin->refresh_peermark_assignments($cm, $tiiassignment->value);
        break;

    case "peermarkmanager":

        if ($userrole == 'Instructor') {
            $plagiarism_plugin_turnitin = new plagiarism_plugin_turnitin();
            $coursedata = $plagiarism_plugin_turnitin->get_course_data($cm->id, $cm->course);

            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cm->id, 'name' => 'turnitin_assignid'));

            if ($tiiassignment) {
                $tiiassignmentid = $tiiassignment->value;
            } else {
                // Create the module as an assignment in Turnitin.
                $tiiassignment = $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid);
                $tiiassignmentid = $tiiassignment['tiiassignmentid'];
            }

            $user = new turnitintooltwo_user($USER->id, "Instructor");
            $user->join_user_to_class($coursedata->turnitin_cid);

            echo html_writer::tag("div", turnitintooltwo_view::output_lti_form_launch('peermark_manager',
                                                        'Instructor', $tiiassignmentid),
                                                        array("class" => "launch_form", "style" => "display:none;"));
            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }
        break;

    case "rubricview":
        $isstudent = ($cm->modname == "forum") ? has_capability('mod/'.$cm->modname.':replypost', $context) :
                                                has_capability('mod/'.$cm->modname.':submit', $context);
        if ($isstudent) {
            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cm->id, 'name' => 'turnitin_assignid'));

            $user = new turnitintooltwo_user($USER->id, "Learner");
            $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP');
            $user->join_user_to_class($coursedata->turnitin_cid);

            echo html_writer::tag("div", turnitintooltwo_view::output_lti_form_launch('rubric_view',
                                                        'Learner', $tiiassignment->value),
                                                        array("class" => "launch_form", "style" => "display:none;"));
            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }
        break;

    case "peermarkreviews":

        $isstudent = ($cm->modname == "forum") ? has_capability('mod/'.$cm->modname.':replypost', $context) :
                                                has_capability('mod/'.$cm->modname.':submit', $context);

        if ($userrole == 'Instructor' || $isstudent) {
            $role = ($istutor) ? 'Instructor' : 'Learner';

            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cm->id, 'name' => 'turnitin_assignid'));

            echo html_writer::tag("div", turnitintooltwo_view::output_lti_form_launch('peermark_reviews',
                                                        $role, $tiiassignment->value),
                                                        array("class" => "launch_form", "style" => "display:none;"));
            echo html_writer::script("<!--
                                    window.document.forms[0].submit();
                                    //-->");
        }
        break;

    case "actionuseragreement":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        $message = optional_param('message', '', PARAM_ALPHAEXT);

        // Get the id from the turnitintooltwo_users table so we can update
        $turnitin_user = $DB->get_record('turnitintooltwo_users', array('userid' => $USER->id));

        // Build user object for update
        $eula_user = new object();
        $eula_user->id = $turnitin_user->id;
        $eula_user->user_agreement_accepted = 0;
        if ($message == 'turnitin_eula_accepted') {
            $eula_user->user_agreement_accepted = 1;
            turnitintooltwo_activitylog("User ".$USER->id." (".$turnitin_user->turnitin_uid.") accepted the EULA.", "PP_EULA_ACCEPTANCE");
        } else if ($message == 'turnitin_eula_declined') {
            $eula_user->user_agreement_accepted = -1;
            turnitintooltwo_activitylog("User ".$USER->id." (".$turnitin_user->turnitin_uid.") declined the EULA.", "PP_EULA_ACCEPTANCE");
        }

        // Update the user using the above object
        $DB->update_record('turnitintooltwo_users', $eula_user, $bulk=false);
        break;

    case "resubmit_event":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        $forumdata = optional_param('forumdata', '', PARAM_ALPHAEXT);
        $forumpost = optional_param('forumpost', '', PARAM_ALPHAEXT);
        $submissionid = required_param('submissionid', PARAM_INT);

        $tiisubmission = new turnitin_submission($submissionid,
                                                array('forumdata' => $forumdata, 'forumpost' => $forumpost));
        $tiisubmission->recreate_submission_event();
        break;

    case "resubmit_events":

        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        $submissionids = optional_param_array('submission_ids', array(), PARAM_INT);

        $submissionids = optional_param_array('submission_ids', array(), PARAM_INT);
        $errors = array();
        $return['success'] = true;
        foreach ($submissionids as $submissionid) {
            $tiisubmission = new turnitin_submission($submissionid);
            if (!$tiisubmission->recreate_submission_event()) {
                $return['success'] = false;
                $errors[] = $submissionid;
            }
        }
        $return['errors'] = $errors;
        break;
}

if (!empty($return)) {
    echo json_encode($return);
}

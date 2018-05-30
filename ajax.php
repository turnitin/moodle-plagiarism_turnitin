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
if ( !empty( $cmid ) ) {
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
                                        $turnitintooltwoview->output_dv_launch_form($dvtype, $submissionid, $user->tiiuserid,
                                                                    $userrole, ''), array('style' => 'display: none'));
        }
        break;

    case "update_grade":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        include_once($CFG->libdir."/gradelib.php");

        $submissionid = optional_param('submission', 0, PARAM_INT);

        if ($userrole == 'Instructor') {
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
                $moduleconfigvalue->config_hash = $moduleconfigvalue->cm."_".$moduleconfigvalue->name;
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
            $plagiarismpluginturnitin = new plagiarism_plugin_turnitin();
            $coursedata = $plagiarismpluginturnitin->get_course_data($cm->id, $cm->course);

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
        $replypost = 'mod/'.$cm->modname.':replypost';
        $submit = 'mod/'.$cm->modname.':submit';
        $isstudent = ($cm->modname == "forum") ? has_capability($replypost, $context) : has_capability($submit, $context);

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
        $replypost = 'mod/'.$cm->modname.':replypost';
        $submit = 'mod/'.$cm->modname.':submit';
        $isstudent = ($cm->modname == "forum") ? has_capability($replypost, $context) : has_capability($submit, $context);

        if ($userrole == 'Instructor' || $isstudent) {
            $tiiassignment = $DB->get_record('plagiarism_turnitin_config', array('cm' => $cm->id, 'name' => 'turnitin_assignid'));

            $user = new turnitintooltwo_user($USER->id, $userrole);
            $coursedata = turnitintooltwo_assignment::get_course_data($cm->course, 'PP');
            $user->join_user_to_class($coursedata->turnitin_cid);

            echo html_writer::tag("div", turnitintooltwo_view::output_lti_form_launch('peermark_reviews',
                                                        $userrole, $tiiassignment->value),
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

        // Get the id from the turnitintooltwo_users table so we can update.
        $turnitinuser = $DB->get_record('turnitintooltwo_users', array('userid' => $USER->id));

        // Build user object for update.
        $eulauser = new stdClass();
        $eulauser->id = $turnitinuser->id;
        $eulauser->user_agreement_accepted = 0;
        if ($message == 'turnitin_eula_accepted') {
            $eulauser->user_agreement_accepted = 1;
            turnitintooltwo_activitylog("User ".$USER->id." (".$turnitinuser->turnitin_uid.") accepted the EULA.", "PP_EULA_ACCEPTANCE");
        } else if ($message == 'turnitin_eula_declined') {
            $eulauser->user_agreement_accepted = -1;
            turnitintooltwo_activitylog("User ".$USER->id." (".$turnitinuser->turnitin_uid.") declined the EULA.", "PP_EULA_ACCEPTANCE");
        }

        // Update the user using the above object.
        $DB->update_record('turnitintooltwo_users', $eulauser, $bulk = false);
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

    case "test_connection":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }
        $data = array("connection_status" => "fail", "msg" => get_string('connecttestcommerror', 'turnitintooltwo'));

        $PAGE->set_context(context_system::instance());
        if (is_siteadmin()) {
            // Initialise API connection.

            $accountid = required_param('accountid', PARAM_RAW);
            $accountshared = required_param('accountshared', PARAM_RAW);
            $url = required_param('url', PARAM_RAW);

            $turnitincomms = new turnitintooltwo_comms($accountid, $accountshared, $url);

            // We only want an API log entry for this if diagnostic mode is set to Debugging.
            if (empty($config)) {
                $config = plagiarism_plugin_turnitin::plagiarism_turnitin_admin_config();
            }
            if ($config->plagiarism_turnitin_enablediagnostic != 2) {
                $turnitincomms->set_diagnostic(0);
            }

            $tiiapi = $turnitincomms->initialise_api(true);

            $class = new TiiClass();
            $class->setTitle('Test finding a class to see if connection works');

            try {
                $response = $tiiapi->findClasses($class);
                $data["connection_status"] = 200;
                $data["msg"] = get_string('connecttestsuccess', 'turnitintooltwo');
            } catch (Exception $e) {
                $turnitincomms->handle_exceptions($e, 'connecttesterror', false);
            }
        }
        echo json_encode($data);
        break;
}

if (!empty($return)) {
    echo json_encode($return);
}

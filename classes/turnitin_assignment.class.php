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
 * @package   plagiarism_turnitin
 * @copyright 2012 iParadigms LLC *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot.'/plagiarism/turnitin/turnitin_comms.class.php');
require_once($CFG->dirroot.'/mod/turnitintooltwo/turnitintooltwo_user.class.php');
require_once($CFG->dirroot.'/plagiarism/turnitin/turnitin_submission.class.php');

class turnitin_assignment {

    private $timecreated;
    private $id;

    public function __construct($id = 0) {
        $this->id = $id;
    }

    /**
     * Find the course data, including Turnitin id
     *
     * @param int $courseid The ID of the course to get the data for
     * @param string $coursetype whether the course is TT (Turnitintool) or PP (Plagiarism Plugin)
     * @param string $workflowcontext whether we are in a cron context (from PP) or using the site as normal.
     * @return object The course object with the Turnitin Class data if it's been created
     */
    public static function get_course_data($courseid, $coursetype = "TT", $workflowcontext = "site") {
        global $DB;

        if (!$course = $DB->get_record("course", array("id" => $courseid))) {
            if ($workflowcontext != "cron") {
                turnitintooltwo_print_error('coursegeterror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                exit;
            }
        }

        $course->turnitin_cid = 0;
        $course->turnitin_ctl = "";
        $course->course_type = $coursetype;
        $course->tii_rel_id = '';
        if ($turnitincourse = $DB->get_record('turnitintooltwo_courses',
            array("courseid" => $courseid, "course_type" => $coursetype))) {
            $course->turnitin_cid = $turnitincourse->turnitin_cid;
            $course->turnitin_ctl = $turnitincourse->turnitin_ctl;
            $course->course_type = $turnitincourse->course_type;
            $course->ownerid = $turnitincourse->ownerid;
            $course->tii_rel_id = $turnitincourse->id;
        }

        return $course;
    }

    /**
     * Create the course in Turnitin
     *
     * @global type $DB
     * @param object $course The course object
     * @param int $ownerid The owner of the course
     * @param string $coursetype whether the course is TT (Turnitintool) or PP (Plagiarism Plugin)
     * @return object the turnitin course if created
     */
    public function create_tii_course($course, $ownerid, $coursetype = "TT", $workflowcontext = "site") {
        global $DB;

        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $class = new TiiClass();
        $tiititle = $this->truncate_title( $course->fullname, TURNITIN_COURSE_TITLE_LIMIT, $coursetype );
        $class->setTitle( $tiititle );

        try {
            $response = $turnitincall->createClass($class);
            $newclass = $response->getClass();

            $turnitincourse = new stdClass();
            $turnitincourse->courseid = $course->id;
            $turnitincourse->ownerid = $ownerid;
            $turnitincourse->turnitin_cid = $newclass->getClassId();
            $turnitincourse->turnitin_ctl = $course->fullname . " (Moodle ".$coursetype.")";
            $turnitincourse->course_type = $coursetype;

            if (empty($course->tii_rel_id)) {
                $method = "insert_record";
            } else {
                $method = "update_record";
                $turnitincourse->id = $course->tii_rel_id;
            }

            if (!$insertid = $DB->$method('turnitintooltwo_courses', $turnitincourse)) {
                turnitintooltwo_print_error('classupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                exit();
            }

            if (empty($turnitincourse->id)) {
                $turnitincourse->id = $insertid;
            }

            turnitintooltwo_activitylog("Class created - ".$turnitincourse->courseid." | ".$turnitincourse->turnitin_cid.
                " | ".$course->fullname . " (Moodle ".$coursetype.")" , "REQUEST");

            return $turnitincourse;
        } catch (Exception $e) {
            $toscreen = true;
            if ($workflowcontext == "cron") {
                mtrace(get_string('pp_classcreationerror', 'turnitintooltwo'));
                $toscreen = false;
            }
            $turnitincomms->handle_exceptions($e, 'classcreationerror', $toscreen);
        }
    }

    /**
     * Edit the course title in Turnitin
     *
     * @global type $DB
     * @param var $course The course object
     * @param string $coursetype whether the course is TT (Turnitintool) or PP (Plagiarism Plugin)
     */
    public function edit_tii_course($course, $coursetype = "TT") {
        global $DB;

        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $class = new TiiClass();
        $class->setClassId($course->turnitin_cid);
        $title = $this->truncate_title( $course->fullname, TURNITIN_COURSE_TITLE_LIMIT, $coursetype );
        $class->setTitle( $title );

        try {
            $turnitincall->updateClass($class);

            $turnitincourse = new stdClass();

            $turnitintooltwocourse = $DB->get_record("turnitintooltwo_courses",
                array("courseid" => $course->id, "course_type" => $coursetype));
            $turnitincourse->id = $turnitintooltwocourse->id;
            $turnitincourse->courseid = $course->id;
            $turnitincourse->ownerid = $turnitintooltwocourse->ownerid;
            $turnitincourse->turnitin_cid = $course->turnitin_cid;
            $turnitincourse->turnitin_ctl = $course->fullname . " (Moodle ".$coursetype.")";
            $turnitincourse->course_type = $coursetype;

            if (!$insertid = $DB->update_record('turnitintooltwo_courses', $turnitincourse) && $coursetype != "PP") {
                turnitintooltwo_print_error('classupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                exit();
            } else {
                turnitintooltwo_activitylog("Class edited - ".$turnitincourse->turnitin_ctl.
                    " (".$turnitincourse->id.")", "REQUEST");
            }
        } catch (Exception $e) {
            $toscreen = ($coursetype == "PP") ? false : true;
            $turnitincomms->handle_exceptions($e, 'classupdateerror', $toscreen);
        }
    }

    /**
     * Truncate the course and assignment titles to match Turnitin requirements and add a coursetype suffix on the end.
     *
     * @param string $title The course id on Turnitin
     * @param int $limit The course title on Turnitin
     * @param string $coursetype whether the course is TT (Turnitintooltwo) or PP (Plagiarism Plugin)
     */
    public static function truncate_title($title, $limit, $coursetype) {
        $suffix = " (Moodle " . $coursetype . ")";
        $limit = $limit - strlen($suffix);
        $truncatedtitle = "";

        if ( mb_strlen( $title, 'UTF-8' ) > $limit ) {
            $truncatedtitle .= mb_substr( $title, 0, $limit - 3, 'UTF-8' ) . "...";
        } else {
            $truncatedtitle .= $title;
        }
        $truncatedtitle .= $suffix;

        return $truncatedtitle;
    }

    /**
     * Create Assignment on Turnitin and return id, delete the instance if it fails
     *
     * @global type $DB
     * @param object $assignment add assignment instance
     * @param var $toolid turnitintooltwo id
     */
    public static function create_tii_assignment($assignment, $toolid, $partnumber,
                                                 $usecontext = "turnitintooltwo", $workflowcontext = "site") {
        global $DB;
        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $response = $turnitincall->createAssignment($assignment);
            $newassignment = $response->getAssignment();

            turnitintooltwo_activitylog("Part created as Turnitin Assignment (".$newassignment->getAssignmentId().
                ") - Tool Id: (".$toolid.") - Part num: (".$partnumber.")", "REQUEST");

            if ($usecontext == "turnitintooltwo") {
                $_SESSION["assignment_updated"][$toolid] = time();
            }

            return $newassignment->getAssignmentId();
        } catch (Exception $e) {
            if ($partnumber == 1 && $usecontext == "turnitintooltwo") {
                $DB->delete_records('turnitintooltwo', array('id' => $toolid));
            }
            $toscreen = true;
            if ($workflowcontext == "cron") {
                mtrace(get_string('ppassignmentcreateerror', 'turnitintooltwo'));
                $toscreen = false;
            }
            $turnitincomms->handle_exceptions($e, 'createassignmenterror', $toscreen);
        }
    }

    /**
     * Edit Assignment on Turnitin
     *
     * @param object $assignment edit assignment instance
     */
    public function edit_tii_assignment($assignment, $workflowcontext = "site") {
        // Initialise Comms Object.
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        try {
            $turnitincall->updateAssignment($assignment);

            $_SESSION["assignment_updated"][$assignment->getAssignmentId()] = time();

            turnitintooltwo_activitylog("Turnitin Assignment part updated - id: ".$assignment->getAssignmentId(), "REQUEST");

            return array('success' => true, 'tiiassignmentid' => $assignment->getAssignmentId());

        } catch (Exception $e) {
            $toscreen = true;

            // Separate error handling for the Plagiarism plugin.
            if ($workflowcontext == "cron") {

                $error = new stdClass();
                $error->title = $assignment->getTitle();
                $error->assignmentid = $assignment->getAssignmentId();
                $errorstr = get_string('ppassignmentediterror', 'turnitintooltwo', $error);

                mtrace($errorstr);
                $toscreen = false;
            }

            $turnitincomms->handle_exceptions($e, 'editassignmenterror', $toscreen);

            // Return error string as we use this in the plagiarism plugin.
            if ($workflowcontext == "cron") {
                return array('success' => false, 'error' => $errorstr,
                    'tiiassignmentid' => $assignment->getAssignmentId());
            } else {
                return array('success' => false, 'error' => get_string('editassignmenterror', 'turnitintooltwo'));
            }
        }
    }

    /**
     * Get the Peermark assignments for this activity.
     *
     * @global type $DB
     * @param $tiiassignid
     * @return array
     */
    public function get_peermark_assignments($tiiassignid) {
        global $DB;
        if ($peermarks = $DB->get_records("turnitintooltwo_peermarks", array("parent_tii_assign_id" => $tiiassignid))) {
            return $peermarks;
        } else {
            return array();
        }
    }
}
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
 * Course-level operations for the Turnitin plagiarism plugin.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Handles course-level operations: provisioning, migration, and reset.
 *
 * Extracted from plagiarism_plugin_turnitin in lib.php.
 *
 * @package plagiarism_turnitin
 */
class turnitin_course {
    /**
     * Handle the course_reset_ended event.
     *
     * Deletes plagiarism_turnitin_files and turnitin_assignid config rows for
     * each module type that was included in the reset, then removes the course
     * record from plagiarism_turnitin_courses when all Turnitin-enabled modules
     * were reset.
     *
     * @param \core\event\course_reset_ended $event
     * @return bool Always true.
     */
    public static function course_reset($event): bool {
        global $DB, $CFG;

        $data     = $event->get_data();
        $courseid = (int)$data['other']['reset_options']['courseid'];

        $resetcourse = true;

        $resetassign = 0;
        $resetassignsubmissions = 0;
        if (!empty($data['other']['reset_options']['reset_assign_submissions'])) {
            $resetassign = $data['other']['reset_options']['reset_assign_submissions'];
            $resetassignsubmissions = $resetassign;
        }

        $resetforumall = 0;
        $resetforum    = 0;
        if (!empty($data['other']['reset_options']['reset_forum_all'])) {
            $resetforumall = $data['other']['reset_options']['reset_forum_all'];
            $resetforum    = $resetforumall;
        }

        // Discover supported modules from the classes/modules/ directory.
        $supportedmods = [];
        foreach (scandir($CFG->dirroot . '/plagiarism/turnitin/classes/modules/') as $filename) {
            $filenamear  = explode('.', $filename);
            $classnamear = explode('_', $filenamear[0]);
            $supportedmods[] = $classnamear[1] ?? '';
        }

        foreach ($supportedmods as $supportedmod) {
            if (empty($supportedmod)) {
                continue;
            }

            $module = $DB->get_record('modules', ['name' => $supportedmod]);
            if ($module === false) {
                continue;
            }

            $sql = "SELECT cm.id
                      FROM {course_modules} cm
                RIGHT JOIN {plagiarism_turnitin_config} ptc ON cm.id = ptc.cm
                     WHERE cm.module = :moduleid
                       AND cm.course = :courseid
                       AND ptc.name = 'turnitin_assignid'";

            $modules = $DB->get_records_sql($sql, ['courseid' => $courseid, 'moduleid' => $module->id]);

            if (count($modules) > 0) {
                $resetvar = 'reset' . $supportedmod;
                if (!empty($$resetvar)) {
                    foreach ($modules as $mod) {
                        $DB->delete_records('plagiarism_turnitin_files', ['cm' => $mod->id]);
                        $DB->delete_records('plagiarism_turnitin_config', ['cm' => $mod->id, 'name' => 'turnitin_assignid']);
                    }
                } else {
                    $resetcourse = false;
                }
            }
        }

        if ($resetcourse) {
            $DB->delete_records('plagiarism_turnitin_courses', ['courseid' => $courseid]);
        }

        return true;
    }

    /**
     * Get full course data including Turnitin class ID, provisioning the class if needed.
     *
     * First checks whether a Turnitin class ID is already stored for the course. If not,
     * looks for a legacy ID from a previous plugin version, or creates a new Turnitin class.
     *
     * @param int $cmid Course module id (0 when not editing a specific module).
     * @param int $courseid Moodle course id.
     * @param string $workflowcontext 'site' or 'cron'.
     * @param \plagiarism_plugin_turnitin|null $plugin Plugin instance; injected in tests
     *     to avoid real API calls in create_tii_course() and migrate_previous_course().
     * @return \stdClass Course data with turnitin_cid and turnitin_ctl populated.
     */
    public static function get_course_data(
        int $cmid,
        int $courseid,
        string $workflowcontext = 'site',
        ?\plagiarism_plugin_turnitin $plugin = null
    ): \stdClass {
        $coursedata = turnitin_assignment::get_course_data($courseid, $workflowcontext);

        if (!empty($coursedata->turnitin_cid)) {
            return $coursedata;
        }

        $pluginturnitin = $plugin ?? new \plagiarism_plugin_turnitin();

        // Course may have existed in a previous incarnation of this plugin.
        if ($turnitincid = self::get_previous_course_id($cmid, $courseid)) {
            $coursedata->turnitin_cid = $turnitincid;
            $coursedata = $pluginturnitin->migrate_previous_course($coursedata, $turnitincid);
        } else {
            // Create a new Turnitin class for this course.
            $add = optional_param('add', '', PARAM_TEXT);
            if ($cmid == 0) {
                $tiicoursedata = $pluginturnitin->create_tii_course($cmid, $add, $coursedata, $workflowcontext);
            } else {
                $cm            = get_coursemodule_from_id('', $cmid);
                $tiicoursedata = $pluginturnitin->create_tii_course($cmid, $cm->modname, $coursedata, $workflowcontext);
            }
            $coursedata->turnitin_cid = !empty($tiicoursedata->turnitin_cid) ? $tiicoursedata->turnitin_cid : null;
            $coursedata->turnitin_ctl = !empty($tiicoursedata->turnitin_ctl) ? $tiicoursedata->turnitin_ctl : '';
        }

        return $coursedata;
    }

    /**
     * Look up a Turnitin class ID from a legacy plugin installation.
     *
     * Checks the plagiarism_turnitin_config table for a stored turnitin_assignid,
     * then queries the Turnitin API to resolve the associated class ID.
     *
     * @param int $cmid Course module id.
     * @param int $courseid Moodle course id.
     * @return int|false Turnitin class ID, or false when none found.
     */
    public static function get_previous_course_id(int $cmid, int $courseid) {
        global $DB;

        $tiicourseid = 0;

        if ($tiiassignment = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cmid, 'name' => 'turnitin_assignid'])) {
            $tiicourseid = (new turnitin_assignment(0))->get_course_id_from_assignment_id((int)$tiiassignment->value);
        } else {
            $coursemods = get_course_mods($courseid);
            foreach ($coursemods as $coursemod) {
                if ($coursemod->modname !== 'turnitintooltwo') {
                    if (
                        $tiiassignment = $DB->get_record(
                            'plagiarism_turnitin_config',
                            ['cm' => $coursemod->id, 'name' => 'turnitin_assignid']
                        )
                    ) {
                        $tiicourseid = (new turnitin_assignment(0))->get_course_id_from_assignment_id((int)$tiiassignment->value);
                    }
                }
            }
        }

        return ($tiicourseid > 0) ? $tiicourseid : false;
    }
}

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
 * Send queued submissions to Turnitin.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @author     Jack Milgate http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin\task;

/**
 * Send queued submissions to Turnitin.
 */
class sync_grades extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('syncgrades', 'plagiarism_turnitin');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;
        
        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
        $pluginturnitin = new \plagiarism_plugin_turnitin();
        if (!$pluginturnitin->is_plugin_configured()) {
            return;
        }
        if (!$pluginturnitin->test_turnitin_connection()) {
            mtrace(get_string('ppeventsfailedconnection', 'plagiarism_turnitin'));
            return;
        }

        $one_week_in_seconds = 7 * 24 * 60 * 60;
        $current_time = time();
        $grade_sync_cutoff = $current_time - $one_week_in_seconds;

        // Get list of all PP enabled activity modules that might need grade sync
        $sql = "SELECT ptc.id, ptc.cm, ptc.name, ptc.value, ptc.config_hash, m.name AS modtype,
                      COALESCE(a.duedate, q.timeclose, f.cutoffdate, w.submissionend) AS duedate
                  FROM {plagiarism_turnitin_config} ptc
                  JOIN {course_modules} cm ON cm.id = ptc.cm
                  JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {assign} a ON (m.name = 'assign' AND a.id = cm.instance)
            LEFT JOIN {quiz} q ON (m.name = 'quiz' AND q.id = cm.instance)
            LEFT JOIN {forum} f ON (m.name = 'forum' AND f.id = cm.instance)
            LEFT JOIN {workshop} w ON (m.name = 'workshop' AND w.id = cm.instance)
                WHERE ptc.name = :configname
                  AND m.name IN ('assign', 'quiz', 'forum', 'workshop')";
        $params = ['configname' => 'turnitin_assignid'];
        $grade_sync_assignments = $DB->get_records_sql($sql, $params);

        foreach ($grade_sync_assignments as $assignment) {
            if ($assignment->duedate < $grade_sync_cutoff) {
                continue;
            }

            try {
                $course_id = $DB->get_field('course_modules', 'course', ['id' => $assignment->cm], MUST_EXIST);
                $modinfo = get_fast_modinfo($course_id);
                $cm = $modinfo->get_cm($assignment->cm);
                $status = $pluginturnitin->update_grades_from_tii($cm);
            } catch (\Exception $e) {
                mtrace('Failed to update grade from tii: ' . $e->getMessage());
                continue;
            }

            if ($status) {
                mtrace('Successfully synced grades for cmid ' . $cm->id);
            } else {
               mtrace('No new grades found for cmid ' . $cm->id);
            }

            // Update the last synced time
            $to_write = new \stdClass();
            $to_write->cm = $assignment->cm;
            $to_write->name = 'grades_last_synced';
            $to_write->value = $current_time;
            $to_write->config_hash = $assignment->cm . '_grades_last_synced';

            $record = $DB->get_record('plagiarism_turnitin_config', [ 'name' => 'grades_last_synced', 'cm' => $assignment->cm ]);
            if ($record) {
                $to_write->id = $record->id;
                $DB->update_record('plagiarism_turnitin_config', $to_write);
            } else {
                $DB->insert_record('plagiarism_turnitin_config', $to_write);
            }
        }
    }
}

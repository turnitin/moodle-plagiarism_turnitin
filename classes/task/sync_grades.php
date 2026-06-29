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

require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');

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
        global $DB;

        $one_week_in_seconds = 7 * 24 * 60 * 60;
        $current_time = time();
        $grade_sync_cutoff = $current_time - $one_week_in_seconds;
        mtrace('grade sync cutoff: ' . userdate($grade_sync_cutoff));

        $pluginturnitin = new \plagiarism_plugin_turnitin();

        // Get list of all PP enabled assignments that might need grade sync
        $grade_sync_assingments = $DB->get_records('plagiarism_turnitin_config', ['name' => 'grades_last_synced']);
        foreach ($grade_sync_assingments as $assignment) {
            $course_id = $DB->get_field('course_modules', 'course', ['id' => $assignment->cm], MUST_EXIST);
            $modinfo = get_fast_modinfo($course_id);
            $cm = $modinfo->get_cm($assignment->cm);
            $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
            $dates = \core\activity_dates::get_dates_for_module($cm, 0);

            foreach ($dates as $date) {
                if ($date['dataid'] === 'duedate') {
                    $due_date = $date['timestamp'];
                    if ($due_date < $grade_sync_cutoff && $assignment->value < $current_time) {
                        mtrace('Attempting grade sync for cmid: ' . $assignment->cm . '...');
                        $status = $pluginturnitin->update_grades_from_tii($cm);
                        if ($status) {
                            $assignment->value = $current_time;
                            $DB->update_record('plagiarism_turnitin_config', $assignment);
                            mtrace('Successfully synced grades from Turnitin');
                        } else {
                            mtrace('No new grades found in Turnitin');
                        }
                    }
                }
            }
        }
    }
}

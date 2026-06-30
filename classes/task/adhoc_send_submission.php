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
 * Ad-hoc task to send queued submissions to Turnitin.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @author     Jack Milgate http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin\task;

/**
 * Ad-hoc task which sends one individual submission to Turnitin.
 * Can be run in parallel in order to speed up processing of submissions.
 */
class adhoc_send_submission extends \core\task\adhoc_task {

    /**
     * Get the name of the task.
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('adhoc_sendqueuedsubmission', 'plagiarism_turnitin');
    }

    public static function instance($submission): self {
        $task = new self();
        $task->set_custom_data((object) [
            'submission' => $submission,
        ]);

        $task->set_component('plagiarism_turnitin');

        return $task;
    }

    /**
     * Allow concurrent execution of this task.
     *
     * @return bool
     */
    public function is_no_concurrency_allowed() {
        return false;
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
        $plugin = new \plagiarism_plugin_turnitin();
        if (!$plugin->is_plugin_configured()) {
            return;
        }

        $data = $this->get_custom_data();

        mtrace('Running ad-hoc task adhoc_sendqueuedsubmission for submission: ' . json_encode($data->submission));
        \plagiarism_turnitin_send_single_submission($plugin, $data->submission);
        mtrace('Ad-hoc task complete.');
    }
}

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
 * @author     John McGettrick http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin\task;

/**
 * Send queued submissions to Turnitin.
 */
class send_submissions extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('sendqueuedsubmissions', 'plagiarism_turnitin');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
        $plugin = new \plagiarism_plugin_turnitin();
        if (!$plugin->is_plugin_configured()) {
            return;
        }

        $pluginconfig = get_config('plagiarism_turnitin');
        if ($pluginconfig->plagiarism_turnitin_enableadhocsubmissions) {
            // Don't attempt to call Turnitin if a connection to Turnitin could not be established.
            if (!$plugin->test_turnitin_connection()) {
                mtrace(get_string('ppeventsfailedconnection', 'plagiarism_turnitin'));
                return;
            }
          
            mtrace('Checking for queued submissions...');

            // Grab all queued or pending submissions.
            $queueditems = $DB->get_records_select("plagiarism_turnitin_files",
                "statuscode = 'queued' OR statuscode = 'pending'", null,
                'lastmodified');

            if (empty($queueditems)) {
                mtrace('No queued items found.');
                return;
            }

            mtrace('Found ' . count($queueditems) . ' queued submissions.');
            mtrace('Queueing ad-hoc tasks...');
            foreach ($queueditems as $item) {
              $adhoctask = adhoc_send_submission::instance($item);
              \core\task\manager::queue_adhoc_task($adhoctask, true);
              mtrace('  Queued submission for upload: ' . json_encode($item));
            }
            mtrace('Done.');
        } else {
            mtrace('Sending submissions using scheduled task...');
            plagiarism_turnitin_send_queued_submissions();
            mtrace('Done.');
        }
    }
}

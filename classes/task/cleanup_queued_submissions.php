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
 * Cleanup queued submissions to Turnitin.
 *
 * @package    plagiarism_turnitin
 * @author     Jose Pico <jose.pico@monash.edu>
 * @copyright  2021 Monash University (http://www.monash.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Cleanup queued submissions to Turnitin.
 */
class cleanup_queued_submissions extends \core\task\scheduled_task {


    public function get_name() {
        return get_string('cleanupqueuedsubmissions', 'plagiarism_turnitin');
    }

    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');

        $config = \plagiarism_plugin_turnitin::plagiarism_turnitin_admin_config();
        $cleanuptimeframe = !empty($config->plagiarism_turnitin_cron_submissions_cleanup) ? $config->plagiarism_turnitin_cron_submissions_cleanup : 10800;

        $plugin = new \plagiarism_plugin_turnitin();
        if (!$plugin->is_plugin_configured() || $cleanuptimeframe <= 0) {
            return true;
        }
        $select = "sendattempted IS NOT NULL AND statuscode = 'queued' AND sendattempted <= :sendattempted";

        // Get all the queued files given the conditions $select
        $queued_files = $DB->count_records_select('plagiarism_turnitin_files', $select,
            array('sendattempted' => time() - $cleanuptimeframe));

        // Reset 'sendattempted' value to null
        $DB->set_field_select('plagiarism_turnitin_files', 'sendattempted', null, $select,
            array('sendattempted' => time() - $cleanuptimeframe));

        mtrace("Successfully updated '{$queued_files}' plagiarism_turnitin records...");

        return true;
    }
}

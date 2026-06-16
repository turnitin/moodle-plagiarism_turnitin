<?php
namespace plagiarism_turnitin\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Send queued submissions to Turnitin.
 */
class send_submissions_worker extends \core\task\adhoc_task {

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
        $plugin = new \plagiarism_plugin_turnitin();
        if (!$plugin->is_plugin_configured()) {
            return;
        }
        plagiarism_turnitin_send_queued_submissions();
    }
}

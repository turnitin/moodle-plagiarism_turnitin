<?php

namespace plagiarism_turnitin\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Library function for plagiarism turnitin task function.
 */

class plagiarism_turnitin_task extends \core\task\scheduled_task {

	public function get_name() {
        // Shown in admin screens
        return get_string('task_name', 'plagiarism_turnitin');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $PLAGIARISM_TURNITIN_TASKCALL;

        // Call plagiarism turnitin cron function.
        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
        $PLAGIARISM_TURNITIN_TASKCALL = true;
        plagiarism_turnitin_cron();
    }
}
<?php

namespace plagiarism_turnitin\task;

use plagiarism_turnitin;

require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

class update_scores extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('update_scores_task', 'plagiarism_turnitin');
    }

    public function execute() {

        $plugin = new \plagiarism_plugin_turnitin();

        // Update scores by separate submission type.
        $submissiontypes = array('file', 'text_content', 'forum_post');
        foreach ($submissiontypes as $submissiontype) {
            $plugin->cron_update_scores($submissiontype);
        }

     }

}
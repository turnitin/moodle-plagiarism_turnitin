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

class turnitin_submission {

	private $id;
	private $submissiondata;
	private $cm;

	public function __construct($id) {
		global $DB;

		$this->id = $id;
		$this->submissiondata = $DB->get_record('plagiarism_turnitin_files', array('id' => $id));
		$this->cm = get_coursemodule_from_id('', $this->submissiondata->cm);
	}

	/**
	 * Get all relevant submission data to requeue submission for the cron to process.
	 */
	public function recreate_submission_event() {
		global $DB;

		$eventdata = new stdClass();
		$eventdata->modulename = $this->cm->modname;
        $eventdata->cmid = $this->cm->id;
        $eventdata->courseid = $this->cm->course;
        $eventdata->userid = $this->submissiondata->userid;

        // Some data depends on submission type.
		switch ($this->submissiondata->submissiontype) {
		 	case 'file':
		 		$file = $this->get_file_info();
		 		$eventdata->file = $file;
		 		$eventdata->itemid = $file->get_itemid();
		 		$eventdata->pathnamehashes = array($this->submissiondata->identifier);

        		events_trigger('assessable_file_uploaded', $eventdata);
		 		break;

		 	case 'text_content':
		 		// Create module object
		        $moduleclass = "turnitin_".$cm->modname;
		        $moduleobject = new $moduleclass;

		        $onlinetextdata = $moduleobject->get_onlinetext($this->submissiondata->userid, $this->cm);

				$eventdata->itemid = $submission->id;
				$eventdata->content = $moodletextsubmission->onlinetext;

				events_trigger('assessable_content_uploaded', $eventdata);
		 		break;

		 	case 'forum_post':
		 		//TODO: Get forum text and populate the following fields
		 		//$eventdata->itemid
		 		//$eventdata->content
		 		break;
		}

        $submissiondata = new stdClass();
        $submissiondata->id = $this->id;
        $submissiondata->statuscode = 'resubmitted';

        return $DB->update_record('plagiarism_turnitin_files', $submissiondata);
	}

	/**
	 * Get the file information from Moodle. We really specifically only need the itemid.
	 */
	public function get_file_info() {
		global $DB;
		$fs = get_file_storage();

		if (!$file = $fs->get_file_by_hash($this->submissiondata->identifier)) {
			return false;
		}

		return $file;
	}
}
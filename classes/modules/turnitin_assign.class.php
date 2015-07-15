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

// TODO: Split out all module specific code from plagiarism/turnitin/lib.php
class turnitin_assign {

	private $modname;

	public function __construct() {
		$this->modname = 'assign';
	}

	public function is_tutor($context) {
		return has_capability('mod/'.$this->modname.':grade', $context);
	}

	public function user_enrolled_on_course($context, $userid) {
		return has_capability('mod/'.$this->modname.':submit', $context, $userid);
	}

	public function set_content($linkarray, $moduleid) {
		global $DB;

		$onlinetextdata = $this->get_onlinetext($linkarray["userid"], $cm);

		return (empty($onlinetextdata->onlinetext)) ? '' : $onlinetextdata->onlinetext;
	}

	public function get_onlinetext($userid, $cm) {
		global $DB;

		// Get latest text content submitted as we do not have submission id.
		$submission = $DB->get_recordset('assign_submission',
										array('userid' => $userid, 'assignment' => $cm->instance),
										'id DESC', 'id', 0, 1);

		$moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
                		                    array('submission' => $submission->id), 'onlinetext');

		$onlinetextdata = new stdClass();
		$onlinetextdata->itemid = $submission->id;
		$onlinetextdata->onlinetext = $moodletextsubmission->onlinetext;

		return $onlinetextdata;
	}
}
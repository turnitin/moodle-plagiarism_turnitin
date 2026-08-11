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

namespace plagiarism_turnitin;

/**
 * Value object carrying all data needed to render a submission's Turnitin status links.
 *
 * Assembled by get_links() from database records, Moodle globals and session data,
 * then passed to turnitin_submission_renderer::render() to produce the HTML output.
 * Keeping data assembly and rendering separate makes the rendering logic independently
 * testable without needing a live Moodle page context.
 *
 * @package   plagiarism_turnitin
 * @copyright Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_link_context {
    /** @var \stdClass|null The plagiarism_turnitin_files row, or null if not yet submitted. */
    public ?\stdClass $plagiarismfile;

    /** @var bool Whether the current viewer is a tutor on this activity. */
    public bool $istutor;

    /** @var int Moodle user id of the current viewer ($USER->id). */
    public int $vieweruserid;

    /** @var int Moodle user id of the submission author ($linkarray['userid']). */
    public int $submissionuserid;

    /** @var int[] All user ids who can view this submission (expanded for group submissions). */
    public array $submissionusers;

    /** @var bool True when the current viewer is a group member who did not submit. */
    public bool $isnonsubmitterforgroupassign;

    /** @var string Submission type: 'file', 'text_content', 'forum_post', 'quiz_answer'. */
    public string $submissiontype;

    /** @var int Course module id. */
    public int $cmid;

    /** @var string Module name (assign, forum, quiz, etc.). */
    public string $cmmodname;

    /** @var int Course id. */
    public int $cmcourse;

    /** @var string Moodle wwwroot URL. */
    public string $wwwroot;

    /** @var string|null Raw submission content (used to encode forum post resubmit data). */
    public ?string $submissioncontent;

    /** @var int File size in bytes (used to check errorcode 2 for oversized files). */
    public int $filesize;

    /** @var bool Whether GradeMark is enabled in the plugin config. */
    public bool $usegrademark;

    /** @var bool Whether Peermark is enabled in the plugin config. */
    public bool $enablepeermark;

    /** @var bool Whether students can view their own Originality Report. */
    public bool $showstudentreport;

    /** @var string|null Rubric id if a rubric is configured for this assignment. */
    public ?string $rubric;

    /** @var bool Whether grades have been released to students. */
    public bool $gradesreleased;

    /** @var bool Whether blind marking is active and identities have not been revealed. */
    public bool $blindon;

    /** @var bool Whether a non-negative grade exists for this student in the gradebook. */
    public bool $gradeexists;

    /** @var \stdClass|null Grade item record, or null if not found. */
    public ?\stdClass $gradeitem;

    /** @var array Peermark assignment records for this CM (pre-fetched from Turnitin). */
    public array $peermarkassignments;

    /** @var bool Whether the submission's author has accepted the Turnitin EULA. */
    public bool $submittereulaccepted;

    /**
     * Construct a context object. All properties are public so callers can set
     * them directly, but this constructor provides defaults for optional ones.
     */
    public function __construct() {
        $this->plagiarismfile              = null;
        $this->istutor                     = false;
        $this->vieweruserid                = 0;
        $this->submissionuserid            = 0;
        $this->submissionusers             = [];
        $this->isnonsubmitterforgroupassign = false;
        $this->submissiontype              = 'file';
        $this->cmid                        = 0;
        $this->cmmodname                   = '';
        $this->cmcourse                    = 0;
        $this->wwwroot                     = '';
        $this->submissioncontent           = null;
        $this->filesize                    = 0;
        $this->usegrademark                = false;
        $this->enablepeermark              = false;
        $this->showstudentreport           = false;
        $this->rubric                      = null;
        $this->gradesreleased              = true;
        $this->blindon                     = false;
        $this->gradeexists                 = false;
        $this->gradeitem                   = null;
        $this->peermarkassignments         = [];
        $this->submittereulaccepted        = true;
    }
}

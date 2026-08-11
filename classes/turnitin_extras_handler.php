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
 * LTI launch form handlers for the Turnitin extras page.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Renders the LTI launch forms shown on extras.php.
 *
 * Extracted from extras.php so the logic can be unit-tested without a full
 * Moodle HTTP request. Each method returns an HTML string; extras.php echoes it.
 *
 * @package plagiarism_turnitin
 */
class turnitin_extras_handler {
    /**
     * Build the rubric manager LTI launch form HTML.
     *
     * Looks up the Turnitin class ID for the given course, then delegates to
     * turnitin_view::output_lti_form_launch().
     *
     * @param int $courseid Moodle course id.
     * @param callable|null $ltilaunch Override for output_lti_form_launch(); injected in tests.
     * @return string HTML containing the LTI launch form and auto-submit script.
     */
    public static function render_rubric_manager(int $courseid, ?callable $ltilaunch = null): string {
        global $DB;

        $tiicourse   = $DB->get_record('plagiarism_turnitin_courses', ['courseid' => $courseid]);
        $tiicourseid = (!empty($tiicourse->turnitin_cid)) ? $tiicourse->turnitin_cid : 0;

        $callback = $ltilaunch ?? [turnitin_view::class, 'output_lti_form_launch'];
        $form     = $callback('rubric_manager', 'Instructor', 0, $tiicourseid);

        return \html_writer::tag('div', $form, ['class' => 'launch_form'])
            . \html_writer::script("<!--\n                window.document.forms[0].submit();\n                //-->");
    }

    /**
     * Build the quickmark manager LTI launch form HTML.
     *
     * @param callable|null $ltilaunch Override for output_lti_form_launch(); injected in tests.
     * @return string HTML containing the LTI launch form and auto-submit script.
     */
    public static function render_quickmark_manager(?callable $ltilaunch = null): string {
        $callback = $ltilaunch ?? [turnitin_view::class, 'output_lti_form_launch'];
        $form     = $callback('quickmark_manager', 'Instructor');

        return \html_writer::tag('div', $form, ['class' => 'launch_form'])
            . \html_writer::script("<!--\n                window.document.forms[0].submit();\n                //-->");
    }

    /**
     * Build the user agreement (EULA) LTI launch form HTML.
     *
     * @param int $userid Moodle user id.
     * @param turnitin_user|null $user Pre-built user object; constructed from $userid when null.
     * @param callable|null $launchform Override for output_launch_form(); injected in tests.
     * @return string HTML containing the EULA form and auto-submit script.
     */
    public static function render_user_agreement(
        int $userid,
        ?turnitin_user $user = null,
        ?callable $launchform = null
    ): string {
        global $OUTPUT;

        $tiiuser  = $user ?? new turnitin_user($userid, 'Learner');
        $callback = $launchform ?? [turnitin_view::class, 'output_launch_form'];
        $form     = $callback('useragreement', 0, $tiiuser->tiiuserid, 'Learner', '');

        $output  = $OUTPUT->box_start('tii_eula_launch');
        $output .= $form;
        $output .= $OUTPUT->box_end(true);
        $output .= \html_writer::script("<!--\n                window.document.forms[0].submit();\n                //-->");

        return $output;
    }
}

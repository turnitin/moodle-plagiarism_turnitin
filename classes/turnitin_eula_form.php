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
 * EULA acceptance widget renderer for the Turnitin plagiarism plugin.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

use Integrations\PhpSdk\TiiLTI;

/**
 * Renders the EULA acceptance widget shown on submission pages.
 *
 * Extracted from plagiarism_plugin_turnitin::render_eula_form() in lib.php.
 * Returns an empty string when no EULA prompt is needed (user already accepted,
 * or no Turnitin connection available).
 *
 * @package plagiarism_turnitin
 */
class turnitin_eula_form {
    /**
     * Cached Turnitin connection state across calls on the same page request.
     *
     * Using a static property rather than a static local variable allows tests
     * to reset the cache between test cases via reset_connection_cache().
     *
     * @var bool|null null = not yet tested, true/false = result of test_turnitin_connection()
     */
    private static ?bool $tiiconnection = null;

    /**
     * Reset the cached connection state.
     *
     * Called in tests to ensure each test case gets a fresh connection check
     * rather than inheriting a cached result from a previous test.
     */
    public static function reset_connection_cache(): void {
        self::$tiiconnection = null;
    }

    /**
     * Build the EULA acceptance widget HTML for the given course module.
     *
     * Returns an empty string when:
     * - No connection to Turnitin can be established.
     * - The current user has already accepted the EULA.
     *
     * @param \stdClass $cm     Course module record (needs ->id, ->course, ->modname).
     * @param \plagiarism_plugin_turnitin $plugin Plugin instance; injected in tests to
     *     avoid real API calls in test_turnitin_connection() and get_course_data().
     * @return string HTML for the EULA widget, or '' when not needed.
     */
    public static function render(\stdClass $cm, \plagiarism_plugin_turnitin $plugin): string {
        global $OUTPUT, $USER;

        $output = '';

        // Cache the connection result for the lifetime of this page request.
        if (self::$tiiconnection === null) {
            self::$tiiconnection = $plugin->test_turnitin_connection();
        }

        if (!self::$tiiconnection) {
            return '';
        }

        $coursedata = $plugin->get_course_data($cm->id, $cm->course);

        $user = new turnitin_user($USER->id, 'Learner');
        $user->join_user_to_class($coursedata->turnitin_cid);
        $eulaaccepted = ($user->useragreementaccepted == 0)
            ? $user->get_accepted_user_agreement()
            : $user->useragreementaccepted;

        if (!empty($eulaaccepted)) {
            return '';
        }

        // Build the EULA prompt link and surrounding container.
        $eulalink = \html_writer::tag(
            'span',
            get_string('turnitinppulapre', 'plagiarism_turnitin'),
            ['class' => 'pp_turnitin_eula_link tii_tooltip', 'id' => 'rubric_manager_form']
        );
        $eulaignoredclass = ($eulaaccepted == 0) ? ' pp_turnitin_eula_ignored' : '';
        $eula = \html_writer::tag(
            'div',
            $eulalink,
            ['class' => 'pp_turnitin_eula' . $eulaignoredclass, 'data-userid' => $user->id]
        );

        // Build the noscript fallback LTI launch form.
        $form = turnitin_view::output_launch_form(
            'useragreement',
            0,
            $user->tiiuserid,
            'Learner',
            get_string('turnitinppulapre', 'plagiarism_turnitin'),
            false
        );

        if ($cm->modname !== 'forum') {
            $form .= ' ' . get_string('noscriptula', 'plagiarism_turnitin');
        }

        $noscripteula = \html_writer::tag('noscript', $form, ['class' => 'warning turnitin_ula_noscript']);

        $output .= $eula . $noscripteula;

        // Build the EULA submission form placeholder (populated client-side via JS).
        $turnitincomms = new turnitin_comms();
        $turnitincall  = $turnitincomms->initialise_api();

        $customdata = [
            'disable_form_change_checker' => true,
            'elements' => [['html', $OUTPUT->box('', '', 'useragreement_inputs')]],
        ];

        $eulaform = new turnitin_form(
            $turnitincall->getApiBaseUrl() . TiiLTI::EULAENDPOINT,
            $customdata,
            'POST',
            'eulaWindow',
            ['id' => 'eula_launch']
        );
        $output .= $OUTPUT->box($eulaform->display(), 'tii_useragreement_form', 'useragreement_form');

        return $output;
    }
}

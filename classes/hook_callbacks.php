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
 * @copyright 2025 Turnitin
 * @author    Jack Milgate
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_footer_html_generation;

class hook_callbacks {

    /**
     * This is a workaround to allow the EULA to be displayed on the quiz page.
     * This function fires on every page, but only does anything if the user is on the quiz page.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $CFG, $PAGE;

        // Check whether the user is on the quiz page. If not, we don't need to do anything.
        $requestUri = $_SERVER['REQUEST_URI'];
        $pattern = '#/quiz/view\.php(\?.*)?$#';
        if (!preg_match($pattern, $requestUri)) {
            return;
        }

        // Deduce the course module ID from the URL.
        if (isset($_GET['id'])) {
            $cm = get_coursemodule_from_id('', $_GET['id']);
        } else {
            // If the course module ID is not present in the URL, we cannot proceed.
            return;
        }

        // This checks whether the user has accepted the EULA.
        // If they haven't, it will return the EULA form. If they have, it will return an empty string.
        require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
        $pluginturnitin = new \plagiarism_plugin_turnitin();
        $eulaform = $pluginturnitin->render_eula_form($cm);

        if ($eulaform == '') {
            return;
        }

        // Echo the form onto the page. The quizEula script will then move it to the correct location on the page.
        echo $eulaform;

        // This script hides the "Start Quiz" button and moves the EULA form into the correct place on the page.
        $PAGE->requires->js_call_amd('plagiarism_turnitin/quiz_eula', 'quizEula');

        // This scripts handles the EULA modal, which is displayed when the user clicks the "View EULA" link.
        $PAGE->requires->js_call_amd('plagiarism_turnitin/new_eula_modal', 'newEulaLaunch');
    }
}
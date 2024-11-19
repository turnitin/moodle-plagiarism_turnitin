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
 * @copyright 2012 iParadigms LLC
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_standard_top_of_body_html_generation;

class hook_callbacks {

    /**
     * Hook callback to insert a chunk of html at the start of an html document.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html_generation(before_standard_top_of_body_html_generation $hook): void {
        if (during_initial_install() || !get_config('plagiarism_turnitin', 'version')) {
            return;
        }

        $output = '<div class="turnitin_score_refresh_alert" id="turnitin_score_refresh_alert">' . get_string('turnitin_score_refresh_alert', 'plagiarism_turnitin') . '</div>';
        $hook->add_html($output);
    }
}

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
 * Student-facing disclosure renderer for the Turnitin plagiarism plugin.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Renders the student-facing Turnitin disclosure block shown on submission pages.
 *
 * Implements the logic of plagiarism_plugin_turnitin::print_disclosure(), extracted
 * from lib.php so it can be tested independently.
 *
 * @package plagiarism_turnitin
 */
class turnitin_disclosure {
    /**
     * Build the HTML disclosure block for a course module submission page.
     *
     * Returns an empty string when Turnitin is not applicable to the given
     * context (module type disabled, not enabled for this CM, etc.).
     *
     * @param int $cmid Course module id.
     * @param \plagiarism_plugin_turnitin|null $plugin Plugin instance; injected in tests
     *     to avoid API calls in render_eula_form() and sync_tii_assignment().
     * @return string HTML output, or '' when nothing should be shown.
     */
    public static function render(int $cmid, ?\plagiarism_plugin_turnitin $plugin = null): string {
        global $OUTPUT, $PAGE, $USER, $DB;

        $config = turnitin_settings::admin_config();
        $output = '';

        $cm = get_coursemodule_from_id('', $cmid);

        // Exit if Turnitin is not enabled for this module type.
        if (empty(turnitin_settings::module_enabled('mod_' . $cm->modname))) {
            return '';
        }

        // Exit if Turnitin is not enabled for this specific CM.
        $plagiarismsettings = turnitin_settings::for_cm($cmid);
        if (empty($plagiarismsettings['use_turnitin'])) {
            return '';
        }

        $pluginturnitin = $plugin ?? new \plagiarism_plugin_turnitin();
        $pluginturnitin->load_page_components();

        // Show resubmission warning — not applicable for forum (no file submissions).
        if ($cm->modname !== 'forum') {
            $tiisubmissions = $DB->get_records('plagiarism_turnitin_files', ['userid' => $USER->id, 'cm' => $cm->id]);
            if (current($tiisubmissions)) {
                $genparams = $pluginturnitin->plagiarism_get_report_gen_speed_params();
                $output .= \html_writer::tag(
                    'div',
                    get_string('reportgenspeed_resubmission', 'plagiarism_turnitin', $genparams),
                    ['class' => 'tii_genspeednote']
                );
            }
        }

        // Show the admin-configured agreement text if set.
        if (!empty($config->plagiarism_turnitin_agreement)) {
            $contents = format_text($config->plagiarism_turnitin_agreement, FORMAT_MOODLE, ['noclean' => true]);
            $output .= $OUTPUT->box($contents, 'generalbox boxaligncenter', 'intro');
        }

        // Stop here if API credentials are not configured — EULA and rubric require them.
        if (!turnitin_settings::is_plugin_configured()) {
            return $output;
        }

        // Add EULA acceptance widget if the user has not yet accepted.
        $output .= $pluginturnitin->render_eula_form($cm);

        // Add rubric viewer link when grademark and a rubric are both active.
        if (!empty($config->plagiarism_turnitin_usegrademark) && !empty($plagiarismsettings['plagiarism_rubric'])) {
            $coursedata = $pluginturnitin->get_course_data($cm->id, $cm->course);
            $pluginturnitin->sync_tii_assignment($cm, $coursedata->turnitin_cid);

            $PAGE->requires->js_call_amd('plagiarism_turnitin/new_rubric', 'newRubric');

            $rubricviewlink = \html_writer::tag(
                'span',
                get_string('launchrubricview', 'plagiarism_turnitin'),
                [
                    'class'         => 'rubric_view rubric_view_pp_launch_upload tii_tooltip',
                    'data-courseid' => $cm->course,
                    'data-cmid'     => $cm->id,
                    'title'         => get_string('launchrubricview', 'plagiarism_turnitin'),
                    'id'            => 'rubric_manager_form',
                ]
            );
            $output .= \html_writer::tag(
                'div',
                \html_writer::tag('div', $rubricviewlink, ['class' => 'row_rubric_view']),
                ['class' => 'tii_links_container tii_disclosure_links']
            );
        }

        return $output;
    }
}

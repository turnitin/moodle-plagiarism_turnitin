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
 * Activity settings form helper for the Turnitin plagiarism plugin.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Injects Turnitin plagiarism settings into a Moodle activity add/edit form.
 *
 * This class extracts the form-building logic that was previously on
 * plagiarism_plugin_turnitin in lib.php, placing it alongside the analogous
 * turnitin_defaultsettingsform.
 *
 * @package plagiarism_turnitin
 */
class turnitin_activitysettingsform {
    /**
     * Inject Turnitin form elements into a course-module add/edit form.
     *
     * Guards against the site home page, capability checks, plugin configuration,
     * per-module enable toggles, and bulk-completion page types before mutating
     * $mform in place with fields, disabled-if conditions, and stored defaults.
     *
     * @param \MoodleQuickForm $mform      The activity form being built.
     * @param \context         $context    The course context (used for capability check).
     * @param string           $modulename Module name prefixed with mod_ (e.g. mod_assign),
     *                                     or empty string when not known.
     * @return void
     */
    public static function add_to_form(\MoodleQuickForm $mform, \context $context, string $modulename = ""): void {
        global $PAGE, $COURSE;

        // Don't allow this plugin to be used on the site home page.
        if ($COURSE->id == 1) {
            return;
        }

        if (!has_capability('plagiarism/turnitin:enable', $context)) {
            return;
        }

        // Get Course module id from the URL when editing an existing module.
        $cmid = optional_param('update', null, PARAM_INT);

        // Return no form if the plugin isn't configured.
        if (!turnitin_settings::is_plugin_configured()) {
            return;
        }

        // Check if plagiarism plugin is enabled for this module type.
        if (!empty($modulename)) {
            if (empty(turnitin_settings::module_enabled($modulename))) {
                return;
            }
        }

        // Load per-cm settings, falling back to site defaults on new activity creation.
        $plagiarismvalues = turnitin_settings::for_cm($cmid);

        /*
         * If Turnitin is disabled and settings are sparse (editing an existing activity
         * created before Turnitin was enabled), reload site defaults so the form shows
         * sensible values should the user decide to enable Turnitin.
         */
        if (empty($plagiarismvalues["use_turnitin"]) && count($plagiarismvalues) <= 2) {
            $savedvalues    = $plagiarismvalues;
            $plagiarismvalues = turnitin_settings::for_cm(null);

            // Preserve the explicitly saved use_turnitin value so we don't silently enable it.
            if (isset($savedvalues["use_turnitin"])) {
                $plagiarismvalues["use_turnitin"] = $savedvalues["use_turnitin"];
            }
        }

        $plagiarismelements = turnitin_settings::fields();

        $plagiarismvalues["plagiarism_rubric"] = !empty($plagiarismvalues["plagiarism_rubric"])
            ? $plagiarismvalues["plagiarism_rubric"]
            : 0;

        // Skip form rendering on Moodle's bulk-completion page types (MDL-78528).
        if (
            $PAGE->pagetype !== 'course-editbulkcompletion' &&
            $PAGE->pagetype !== 'course-editdefaultcompletion' &&
            $PAGE->pagetype !== 'course-defaultcompletion'
        ) {
            $course = turnitin_assignment::get_course_data($COURSE->id, "site");
            $turnitinview = new turnitin_view();
            $turnitinview->add_elements_to_settings_form(
                $mform,
                $course,
                "activity",
                $modulename,
                $cmid,
                $plagiarismvalues["plagiarism_rubric"]
            );
        }

        // Disable all fields when use_turnitin is off.
        foreach ($plagiarismelements as $element) {
            if ($element !== 'use_turnitin') {
                $mform->disabledIf($element, 'use_turnitin', 'eq', 0);
            }
        }

        // Apply stored or default values to each field.
        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            }
        }
    }
}

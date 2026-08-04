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
 * Retrieves and stores Turnitin plugin settings.
 *
 * Centralises the three types of settings this plugin manages:
 *   - Plugin-wide admin config (Turnitin account credentials, feature flags)
 *   - Per-module-type enablement (is Turnitin on for mod_assign, mod_quiz, etc.)
 *   - Per-CM settings (report generation, comparison options, etc.) with
 *     site-wide locking applied
 *
 * Usage:
 *   $config   = turnitin_settings::admin_config();
 *   $enabled  = turnitin_settings::module_enabled('mod_assign');
 *   $settings = turnitin_settings::for_cm($cm->id);
 *   turnitin_settings::set_config($data, 'secretkey');
 *
 * @package   plagiarism_turnitin
 * @copyright Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turnitin_settings {

    /**
     * Return the plugin-wide admin config from Moodle's config table.
     *
     * @return \stdClass All config values for the plagiarism_turnitin component.
     */
    public static function admin_config(): \stdClass {
        return get_config('plagiarism_turnitin');
    }

    /**
     * Return whether Turnitin is enabled for a given module type.
     *
     * @param string $modulename Frankenstyle module name, e.g. 'mod_assign'.
     * @return mixed Config value (truthy when enabled) or false if not set.
     */
    public static function module_enabled(string $modulename) {
        return get_config('plagiarism_turnitin', 'plagiarism_turnitin_' . $modulename);
    }

    /**
     * Return the canonical list of per-CM setting field names.
     *
     * Used when building and saving the activity settings form, and when
     * reading which fields should be checked for site-wide locks.
     *
     * @return string[]
     */
    public static function fields(): array {
        return [
            'use_turnitin',
            'plagiarism_show_student_report',
            'plagiarism_draft_submit',
            'plagiarism_allow_non_or_submissions',
            'plagiarism_submitpapersto',
            'plagiarism_compare_student_papers',
            'plagiarism_compare_internet',
            'plagiarism_compare_journals',
            'plagiarism_report_gen',
            'plagiarism_compare_institution',
            'plagiarism_exclude_biblio',
            'plagiarism_exclude_quoted',
            'plagiarism_exclude_matches',
            'plagiarism_exclude_matches_value',
            'plagiarism_rubric',
            'plagiarism_transmatch',
        ];
    }

    /**
     * Return Turnitin settings for a course module, with site-wide locks applied.
     *
     * Site admins can lock individual settings so that activity creators cannot
     * override them. When $uselockedvalues is true (the default), any setting
     * whose corresponding _lock default is 1 is replaced by the site default.
     * Pass false during initial module creation, before per-CM values exist.
     *
     * @param int|null $cmid           Course module ID, or null for site defaults only.
     * @param bool     $uselockedvalues Whether to enforce site-wide locked values.
     * @return array<string, string>   Map of setting name => value.
     */
    public static function for_cm(?int $cmid, bool $uselockedvalues = true): array {
        global $DB;

        $defaults = $DB->get_records_menu('plagiarism_turnitin_config', ['cm' => null], '', 'name,value');
        $settings = $DB->get_records_menu('plagiarism_turnitin_config', ['cm' => $cmid], '', 'name,value');

        if (!$uselockedvalues) {
            return $settings;
        }

        foreach ($defaults as $key => $value) {
            if (substr($key, -5) !== '_lock') {
                continue;
            }
            if ($value != 1) {
                continue;
            }
            $setting = substr($key, 0, -5);
            $settings[$setting] = $defaults[$setting];
        }

        return $settings;
    }

    /**
     * Persist a single admin config value.
     *
     * Accepts either the full prefixed name ('plagiarism_turnitin_secretkey') or
     * the short name ('secretkey') — the prefix is added automatically if absent.
     * Does nothing when $property is not set on $data, so callers can pass the
     * full form object without checking each field individually.
     *
     * @param \stdClass $data     Object whose property holds the value to save.
     * @param string    $property Property name on $data, with or without the prefix.
     */
    public static function set_config(\stdClass $data, string $property): void {
        $field = strpos($property, 'plagiarism_turnitin') === false
            ? 'plagiarism_turnitin_' . $property
            : $property;

        if (isset($data->$property)) {
            set_config($field, $data->$property, 'plagiarism_turnitin');
        }
    }
}

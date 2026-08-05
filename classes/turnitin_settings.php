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

    /**
     * Check whether the plugin has been configured with the three required
     * Turnitin account credentials.
     *
     * @return bool True when accountid, apiurl and secretkey are all non-empty.
     */
    public static function is_plugin_configured(): bool {
        $config = self::admin_config();

        return !empty($config->plagiarism_turnitin_accountid)
            && !empty($config->plagiarism_turnitin_apiurl)
            && !empty($config->plagiarism_turnitin_secretkey);
    }

    /**
     * Check whether at least one comparison source is enabled for an assignment.
     *
     * When all four comparison options are disabled there is nothing for Turnitin
     * to check against, so the submission should not be sent. Returns false in that
     * case, including when the settings keys are absent (treated as disabled).
     *
     * @param array $settings Per-CM settings from turnitin_settings::for_cm().
     * @return bool True when at least one comparison option is enabled.
     */
    public static function has_comparison_options(array $settings): bool {
        return !empty($settings['plagiarism_compare_student_papers'])
            || !empty($settings['plagiarism_compare_internet'])
            || !empty($settings['plagiarism_compare_journals'])
            || !empty($settings['plagiarism_compare_institution']);
    }

    /**
     * Persist the Turnitin settings submitted from an activity edit form.
     *
     * Iterates over the canonical field list and upserts each field that is
     * present in $data into plagiarism_turnitin_config. Fields absent from $data
     * are silently skipped so partial form submissions don't overwrite unrelated
     * settings.
     *
     * Does nothing when Turnitin is not enabled for the module type, so this
     * method is safe to call for any module without a prior enablement check.
     *
     * @param \stdClass $data Form data object — must contain modulename and coursemodule.
     */
    public static function save_for_cm(\stdClass $data): void {
        global $DB;

        if (empty(self::module_enabled('mod_' . $data->modulename))) {
            return;
        }

        $currentvalues = self::for_cm($data->coursemodule, false);

        foreach (self::fields() as $field) {
            if (!isset($data->$field)) {
                continue;
            }

            $optionfield = new \stdClass();
            $optionfield->cm    = $data->coursemodule;
            $optionfield->name  = $field;
            $optionfield->value = $data->$field;

            if (isset($currentvalues[$field])) {
                $optionfield->id = $DB->get_field(
                    'plagiarism_turnitin_config',
                    'id',
                    ['cm' => $data->coursemodule, 'name' => $field]
                );
                if (!$DB->update_record('plagiarism_turnitin_config', $optionfield)) {
                    plagiarism_turnitin_print_error(
                        'defaultupdateerror',
                        'plagiarism_turnitin',
                        null,
                        null,
                        __FILE__,
                        __LINE__
                    );
                }
            } else {
                $optionfield->config_hash = $optionfield->cm . '_' . $optionfield->name;
                if (!$DB->insert_record('plagiarism_turnitin_config', $optionfield)) {
                    plagiarism_turnitin_print_error(
                        'defaultinserterror',
                        'plagiarism_turnitin',
                        null,
                        null,
                        __FILE__,
                        __LINE__
                    );
                }
            }
        }
    }
}

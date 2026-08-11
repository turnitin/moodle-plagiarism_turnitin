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
 * Business logic handlers for the Turnitin admin settings page.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Handles the form submission and data-management actions from settings.php.
 *
 * Extracted from settings.php so the logic can be unit-tested without a full
 * Moodle HTTP request.
 *
 * @package plagiarism_turnitin
 */
class turnitin_settings_actions {
    /**
     * Upsert all per-field default values submitted from the defaults settings form.
     *
     * For each field in turnitin_settings::fields() (plus their _lock counterparts and
     * plagiarism_locked_message), inserts or updates the row in plagiarism_turnitin_config
     * where cm IS NULL.
     *
     * @param array  $plugindefaults Existing defaults keyed by field name, as returned by
     *               turnitin_settings::for_cm(null). Used to decide insert vs update.
     * @param array  $formvalues     Map of field name => submitted value. In production this
     *               comes from optional_param(); tests pass it directly.
     * @return void
     */
    public static function save_defaults(array $plugindefaults, array $formvalues): void {
        global $DB;

        $fields        = turnitin_settings::fields();
        $settingsfields = [];
        foreach ($fields as $field) {
            $settingsfields[] = $field;
            $settingsfields[] = $field . '_lock';
        }
        $settingsfields[] = 'plagiarism_locked_message';

        foreach ($settingsfields as $field) {
            $defaultfield        = new \stdClass();
            $defaultfield->cm    = null;
            $defaultfield->name  = $field;
            $defaultfield->value = $formvalues[$field] ?? '';

            if (isset($plugindefaults[$field])) {
                $defaultfield->id = $DB->get_field(
                    'plagiarism_turnitin_config',
                    'id',
                    ['cm' => null, 'name' => $field]
                );
                if (!$DB->update_record('plagiarism_turnitin_config', $defaultfield)) {
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
                $defaultfield->config_hash = $defaultfield->cm . '_' . $defaultfield->name;
                if (!$DB->insert_record('plagiarism_turnitin_config', $defaultfield)) {
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

    /**
     * Mark a plagiarism_turnitin_files record as deleted.
     *
     * @param int $id Record id in plagiarism_turnitin_files.
     * @return void
     */
    public static function delete_file(int $id): void {
        global $DB;
        $DB->update_record('plagiarism_turnitin_files', ['id' => $id, 'statuscode' => 'deleted']);
    }

    /**
     * Unlink (and optionally relink) a set of Turnitin users.
     *
     * For each id in $userids:
     * - If the Moodle user exists: unlinks from Turnitin via turnitin_user::unlink_user(),
     *   then optionally re-creates them in Turnitin ($relink = true).
     * - If the Moodle user no longer exists: deletes the plagiarism_turnitin_users row.
     *
     * @param int[]  $userids  Row ids from plagiarism_turnitin_users.
     * @param bool   $relink   When true, re-create each user in Turnitin after unlinking.
     * @param callable|null $userfactory Factory callable that returns a turnitin_user given
     *               a Moodle user id and optional args. Injected in tests to avoid API calls.
     *               Signature: function(int $userid, ...$args): turnitin_user
     * @return void
     */
    public static function process_user_links(
        array $userids,
        bool $relink,
        ?callable $userfactory = null
    ): void {
        global $DB;

        foreach ($userids as $tiiid) {
            $tuser = $DB->get_record('plagiarism_turnitin_users', ['id' => $tiiid]);
            $muser = $DB->get_record('user', ['id' => $tuser->userid]);

            if ($muser) {
                // Rebuild email from username for deleted users whose email was blanked.
                if (empty($muser->email) || strpos($muser->email, '@') === false) {
                    $split       = explode('.', $muser->username);
                    array_pop($split);
                    $muser->email = join('.', $split);
                }

                if ($userfactory !== null) {
                    $user = $userfactory($muser->id, null, null, null, false);
                } else {
                    $user = new turnitin_user($muser->id, null, null, null, false);
                }
                $user->unlink_user($tiiid);

                if ($relink) {
                    if ($userfactory !== null) {
                        $userfactory($muser->id);
                    } else {
                        new turnitin_user($muser->id);
                    }
                }
            } else {
                $DB->delete_records('plagiarism_turnitin_users', ['id' => $tiiid]);
            }
        }
    }

    /**
     * Return the list of log file dates available for a given log type.
     *
     * Scans $logsdir for filenames matching $logtype and extracts the date portion.
     * Returns an empty array when the directory does not exist.
     *
     * @param string $logtype  Log type prefix, e.g. 'apilog' or 'activitylog'.
     * @param string $logsdir  Filesystem path to the logs directory.
     * @return string[] Date strings extracted from log filenames (e.g. '2024-01-15').
     */
    public static function list_log_dates(string $logtype, string $logsdir): array {
        $dates = [];

        if (!file_exists($logsdir) || !($readdir = opendir($logsdir))) {
            return $dates;
        }

        while (false !== ($entry = readdir($readdir))) {
            if (substr_count($entry, $logtype) > 0) {
                $split   = preg_split('/_/', $entry);
                $date    = array_pop($split);
                $dates[] = str_replace('.txt', '', $date);
            }
        }

        closedir($readdir);
        return $dates;
    }
}

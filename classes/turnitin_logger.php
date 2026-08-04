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
 * Diagnostic activity logger for the Turnitin plagiarism plugin.
 *
 * Writes timestamped entries to a rotating set of daily log files under
 * $CFG->tempdir/plagiarism_turnitin/logs/ when the diagnostic setting is enabled.
 * Only the 10 most recent daily log files are kept.
 *
 * Usage:
 *   turnitin_logger::log('Assignment created (id: 42)', 'REQUEST');
 *
 * @package   plagiarism_turnitin
 * @copyright Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

class turnitin_logger {

    /** @var int Maximum number of daily log files to retain. */
    const MAX_LOG_FILES = 10;

    /** @var string Filename prefix for log files. */
    const LOG_PREFIX = 'activitylog_';

    /** @var object|null Cached plugin config to avoid repeated DB calls within a request. */
    private static ?object $config = null;

    /**
     * Write a diagnostic log entry.
     *
     * Does nothing when the plagiarism_turnitin_enablediagnostic config setting is off,
     * so it is safe to call unconditionally throughout the codebase.
     *
     * @param string $message  Human-readable description of the event
     * @param string $activity Short code identifying the type of event, e.g. 'REQUEST',
     *                         'API_ERROR', 'PP_NO_FILE'. Used as a filter key in the log.
     */
    public static function log(string $message, string $activity): void {
        global $CFG;

        if (self::$config === null) {
            self::$config = \plagiarism_plugin_turnitin::plagiarism_turnitin_admin_config();
        }

        if (empty(self::$config->plagiarism_turnitin_enablediagnostic)) {
            return;
        }

        $logdir = $CFG->tempdir . '/plagiarism_turnitin/logs';
        if (!file_exists($logdir)) {
            mkdir($logdir, 0777, true);
        }

        self::prune_old_logs($logdir);

        // Replace <br/> tags so HTML error messages are readable as plain text.
        $message = str_replace('<br/>', "\r\n", $message);

        $logfile = $logdir . '/' . self::LOG_PREFIX . gmdate('Y-m-d', time()) . '.txt';
        $fh      = fopen($logfile, 'a');
        fwrite($fh, date('Y-m-d H:i:s O') . ' (' . $activity . ') - ' . $message . "\r\n");
        fclose($fh);
    }

    /**
     * Delete the oldest log files when the directory exceeds MAX_LOG_FILES entries.
     *
     * Files are sorted alphabetically — since filenames are date-prefixed (YYYY-MM-DD)
     * this naturally puts the oldest files first.
     *
     * @param string $logdir Absolute path to the log directory
     */
    private static function prune_old_logs(string $logdir): void {
        $dh    = opendir($logdir);
        $files = [];
        while ($entry = readdir($dh)) {
            $basename = basename($entry);
            if ($basename[0] !== '.' && strpos($basename, self::LOG_PREFIX) !== false) {
                $files[] = $basename;
            }
        }
        closedir($dh);

        sort($files);
        $excess = count($files) - self::MAX_LOG_FILES;
        for ($i = 0; $i < $excess; $i++) {
            unlink($logdir . '/' . $files[$i]);
        }
    }

    /**
     * Reset the cached config. Should only be called in unit tests when the plugin
     * configuration has been changed via set_config().
     */
    public static function reset_config_cache(): void {
        self::$config = null;
    }
}

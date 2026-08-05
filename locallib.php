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
 * Extra helper methods for plagiarism_turnitin component
 *
 * @package   plagiarism_turnitin
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Override the repository option if necessary depending on the configuration setting.
 * @param int $submitpapersto - The repository to submit to.
 * @return $submitpapersto int - The repository to submit to.
 */
function plagiarism_turnitin_override_repository($submitpapersto) {
    $config = \plagiarism_turnitin\turnitin_settings::admin_config();

    switch ($config->plagiarism_turnitin_repositoryoption) {
        case PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD: // Force Standard Repository.
            $submitpapersto = PLAGIARISM_TURNITIN_SUBMIT_TO_STANDARD_REPOSITORY;
            break;
        case PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_NO: // Force No Repository.
            $submitpapersto = PLAGIARISM_TURNITIN_SUBMIT_TO_NO_REPOSITORY;
            break;
        case PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_INSTITUTIONAL: // Force Individual Repository.
            $submitpapersto = PLAGIARISM_TURNITIN_SUBMIT_TO_INSTITUTIONAL_REPOSITORY;
            break;
    }

    return $submitpapersto;
}

/**
 * Retrieve previously made successful submissions that match passed in parameters. This
 * avoids resubmitting them to Turnitin.
 *
 * @param string $author The author of the submission.
 * @param int $cmid The course module id.
 * @param int $identifier The identifier of the submission.
 * @return $plagiarismfiles - an array of succesfully submitted submissions
 */
function plagiarism_turnitin_retrieve_successful_submissions($author, $cmid, $identifier) {
    global $CFG, $DB;

    // Check if the same answer has been submitted previously. Remove if so.
    [$insql, $inparams] = $DB->get_in_or_equal(['success', 'queued'], SQL_PARAMS_QM, 'param', false);
    $typefield = ($CFG->dbtype == "oci") ? " to_char(statuscode) " : " statuscode ";

    $plagiarismfiles = $DB->get_records_select(
        "plagiarism_turnitin_files",
        " userid = ? AND cm = ? AND identifier = ? AND " . $typefield . " " . $insql,
        array_merge([$author, $cmid, $identifier], $inparams)
    );

    return $plagiarismfiles;
}

/**
 * Add a config field to show submissions have been made which we use to lock the anonymous marking setting.
 * @param int $cmid The course module id.
 */
function plagiarism_turnitin_lock_anonymous_marking($cmid) {
    global $DB;

    $configfield = new stdClass();
    $configfield->cm = $cmid;
    $configfield->name = 'submitted';
    $configfield->value = 1;
    $configfield->config_hash = $configfield->cm . "_" . $configfield->name;

    if (
        !$DB->get_field(
            'plagiarism_turnitin_config',
            'id',
            (['cm' => $cmid, 'name' => 'submitted'])
        )
    ) {
        if (!$DB->insert_record('plagiarism_turnitin_config', $configfield)) {
            plagiarism_turnitin_print_error(
                'defaultupdateerror',
                'plagiarism_turnitin',
                null,
                null,
                __FILE__,
                __LINE__
            );
        }
    }
}

/**
 * Check whether a user has accepted the Turnitin EULA in the local database.
 *
 * Only queries the local plagiarism_turnitin_users table — makes no API call.
 * Returns false when no record exists, the user hasn't yet accepted, or they
 * explicitly declined (user_agreement_accepted = -1). The caller is responsible
 * for making the live API call to prompt acceptance when this returns false.
 *
 * @param int $userid Moodle user id.
 * @return bool True only when user_agreement_accepted = 1.
 */
function plagiarism_turnitin_is_eula_accepted(int $userid): bool {
    global $DB;

    $tiiuser = $DB->get_record('plagiarism_turnitin_users', ['userid' => $userid], 'user_agreement_accepted');

    return $tiiuser !== false && $tiiuser->user_agreement_accepted == 1;
}

/**
 * Abstracted error handler that logs the error and throws a moodle_exception.
 *
 * Constructs a redirect URL from the current page context when $link is not
 * supplied, falling back to $CFG->wwwroot when no recognised module page is detected.
 *
 * @param string $input  Language string key, or raw message when $module is null.
 * @param string $module Plugin/component name for get_string(); pass null to use $input as-is.
 * @param string $link   URL to redirect to on error; auto-detected from PHP_SELF if null.
 * @param mixed  $param  Optional $a object/array passed to get_string().
 * @param string $file   File where the error occurred (for non-lib.php callers).
 * @param int    $line   Line number where the error occurred.
 */
function plagiarism_turnitin_print_error(
    $input,
    $module = 'plagiarism_turnitin',
    $link = null,
    $param = null,
    $file = __FILE__,
    $line = __LINE__
) {
    global $CFG;

    \plagiarism_turnitin\turnitin_logger::log($input, 'PRINT_ERROR');

    $message = is_null($module) ? $input : get_string($input, $module, $param);
    $linkid  = optional_param('id', 0, PARAM_INT);

    if (is_null($link)) {
        $mod = '';
        if (substr_count($_SERVER['PHP_SELF'], 'assign/view.php') > 0) {
            $mod = 'assign';
        } else if (substr_count($_SERVER['PHP_SELF'], 'forum/view.php') > 0) {
            $mod = 'forum';
        } else if (substr_count($_SERVER['PHP_SELF'], 'workshop/view.php') > 0) {
            $mod = 'workshop';
        }
        $link = (!empty($linkid) && !empty($mod))
            ? $CFG->wwwroot . '/' . $mod . '/view.php?id=' . $linkid
            : $CFG->wwwroot;
    }

    if (basename($file) !== 'lib.php') {
        $message .= ' (' . basename($file) . ' | ' . $line . ')';
    }

    throw new \moodle_exception($input, 'plagiarism_turnitin', $link, $message);
}

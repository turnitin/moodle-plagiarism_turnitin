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
 * Business logic handlers for the Turnitin AJAX endpoint.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

use Integrations\PhpSdk\TiiClass;

/**
 * Handles the data-management actions dispatched from ajax.php.
 *
 * Extracted from ajax.php so the logic can be unit-tested without a full
 * Moodle HTTP request.
 *
 * @package plagiarism_turnitin
 */
class turnitin_ajax_handler {
    /**
     * Record a user's EULA acceptance or decline in the database.
     *
     * Updates user_agreement_accepted in plagiarism_turnitin_users:
     *   - 'turnitin_eula_accepted'  → 1
     *   - 'turnitin_eula_declined'  → -1
     *   - any other value           → 0
     *
     * @param int    $userid  Moodle user id.
     * @param string $message One of 'turnitin_eula_accepted' or 'turnitin_eula_declined'.
     * @return void
     */
    public static function action_user_agreement(int $userid, string $message): void {
        global $DB;

        $turnitinuser = $DB->get_record('plagiarism_turnitin_users', ['userid' => $userid]);

        $eulauser     = new \stdClass();
        $eulauser->id = $turnitinuser->id;
        $eulauser->user_agreement_accepted = 0;

        if ($message === 'turnitin_eula_accepted') {
            $eulauser->user_agreement_accepted = 1;
            $logstring = "User {$userid} ({$turnitinuser->turnitin_uid}) accepted the EULA.";
            turnitin_logger::log($logstring, 'PP_EULA_ACCEPTANCE');
        } else if ($message === 'turnitin_eula_declined') {
            $eulauser->user_agreement_accepted = -1;
            $logstring = "User {$userid} ({$turnitinuser->turnitin_uid}) declined the EULA.";
            turnitin_logger::log($logstring, 'PP_EULA_ACCEPTANCE');
        }

        $DB->update_record('plagiarism_turnitin_users', $eulauser);
    }

    /**
     * Upsert the grades_last_synced timestamp for a course module.
     *
     * Inserts a new plagiarism_turnitin_config row for 'grades_last_synced' if one
     * does not exist, or updates the existing row with the current timestamp.
     *
     * @param int $cmid Course module id.
     * @return void
     */
    public static function record_grade_sync_timestamp(int $cmid): void {
        global $DB;

        $configvalue = new \stdClass();
        $configvalue->value = time();

        if (
            $existing = $DB->get_record(
                'plagiarism_turnitin_config',
                ['cm' => $cmid, 'name' => 'grades_last_synced'],
                'id'
            )
        ) {
            $configvalue->id = $existing->id;
            $DB->update_record('plagiarism_turnitin_config', $configvalue);
        } else {
            $configvalue->cm          = $cmid;
            $configvalue->name        = 'grades_last_synced';
            $configvalue->config_hash = $cmid . '_grades_last_synced';
            $DB->insert_record('plagiarism_turnitin_config', $configvalue);
        }
    }

    /**
     * Recreate the submission event for a single submission.
     *
     * @param int $submissionid ID from plagiarism_turnitin_files.
     * @param string $forumdata Optional forum data string.
     * @param string $forumpost Optional base64-encoded forum post.
     * @param turnitin_submission|null $submission Pre-built submission object; injected in tests.
     * @return bool True when the event was recreated successfully.
     */
    public static function resubmit_event(
        int $submissionid,
        string $forumdata = '',
        string $forumpost = '',
        ?turnitin_submission $submission = null
    ): bool {
        $tiisubmission = $submission ?? new turnitin_submission(
            $submissionid,
            ['forumdata' => $forumdata, 'forumpost' => $forumpost]
        );

        return (bool)$tiisubmission->recreate_submission_event();
    }

    /**
     * Recreate submission events for a batch of submissions.
     *
     * @param int[] $submissionids IDs from plagiarism_turnitin_files.
     * @param callable|null $submissionfactory Factory callable that returns a
     *     turnitin_submission given a submission id. Injected in tests.
     * @return array{success: bool, errors: int[]}
     */
    public static function resubmit_events(array $submissionids, ?callable $submissionfactory = null): array {
        $errors  = [];
        $success = true;

        foreach ($submissionids as $submissionid) {
            $submission = $submissionfactory
                ? $submissionfactory($submissionid)
                : new turnitin_submission($submissionid);

            if (!$submission->recreate_submission_event()) {
                $success   = false;
                $errors[]  = $submissionid;
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }

    /**
     * Test a Turnitin API connection with the given credentials.
     *
     * Attempts to call findClasses() on the Turnitin SDK. Returns an array with
     * 'connection_status' (200 on success, 'fail' on error) and 'msg'.
     *
     * @param string $accountid     Turnitin account ID.
     * @param string $accountshared Turnitin secret key.
     * @param string $url           Turnitin API URL.
     * @param turnitin_comms|null $comms Comms instance; injected in tests to avoid a real connection.
     * @return array{connection_status: int|string, msg: string}
     */
    public static function test_connection(
        string $accountid,
        string $accountshared,
        string $url,
        ?turnitin_comms $comms = null
    ): array {
        $data = [
            'connection_status' => 'fail',
            'msg'               => get_string('connecttestcommerror', 'plagiarism_turnitin'),
        ];

        $turnitincomms = $comms ?? new turnitin_comms($accountid, $accountshared, $url);

        $config = turnitin_settings::admin_config();
        if (
            empty($config->plagiarism_turnitin_enablediagnostic)
            || $config->plagiarism_turnitin_enablediagnostic != 2
        ) {
            $turnitincomms->set_diagnostic(0);
        }

        $tiiapi = $turnitincomms->initialise_api(true);
        $class  = new TiiClass();
        $class->setTitle('Test finding a class to see if connection works');

        try {
            $tiiapi->findClasses($class);
            $data['connection_status'] = 200;
            $data['msg']               = get_string('connecttestsuccess', 'plagiarism_turnitin');
        } catch (\Exception $e) {
            $turnitincomms->handle_exceptions($e, 'connecttesterror', false);
        }

        return $data;
    }
}

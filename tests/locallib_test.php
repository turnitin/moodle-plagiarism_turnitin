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
 * Unit tests for (some of) plagiarism/turnitin/locallib.php.
 *
 * @package   plagiarism_turnitin
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for locallib class
 *
 * @package turnitin
 */
#[CoversFunction('\plagiarism_turnitin_override_repository')]
#[CoversFunction('\plagiarism_turnitin_retrieve_successful_submissions')]
#[CoversFunction('\plagiarism_turnitin_lock_anonymous_marking')]
#[CoversFunction('\plagiarism_turnitin_is_eula_accepted')]
#[CoversFunction('\plagiarism_turnitin_print_error')]
final class locallib_test extends \advanced_testcase {
    /**
     * Test that we have the correct repository depending on the config settings.
     */
    public function test_plagiarism_turnitin_override_repository(): void {
        $this->resetAfterTest();

        // Note that $submitpapersto would only ever be 0, 1 or 2 but this is to illustrate
        // that it won't be overridden by the plagiarism_turnitin_override_repository method.
        $submitpapersto = 6;

        // Test that repository is not overridden for value of 0.
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_STANDARD,
            'plagiarism_turnitin'
        );
        $response = plagiarism_turnitin_override_repository($submitpapersto);
        $this->assertEquals($submitpapersto, $response);

        // Test that repository is not overridden for value of 1.
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_EXPANDED,
            'plagiarism_turnitin'
        );
        $response = plagiarism_turnitin_override_repository($submitpapersto);
        $this->assertEquals($submitpapersto, $response);

        // Standard Repository is being forced.
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD,
            'plagiarism_turnitin'
        );
        $response = plagiarism_turnitin_override_repository($submitpapersto);
        $this->assertEquals(PLAGIARISM_TURNITIN_SUBMIT_TO_STANDARD_REPOSITORY, $response);

        // No Repository is being forced.
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_NO,
            'plagiarism_turnitin'
        );
        $response = plagiarism_turnitin_override_repository($submitpapersto);
        $this->assertEquals(PLAGIARISM_TURNITIN_SUBMIT_TO_NO_REPOSITORY, $response);

        // Institutional Repository is being forced.
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_INSTITUTIONAL,
            'plagiarism_turnitin'
        );
        $response = plagiarism_turnitin_override_repository($submitpapersto);
        $this->assertEquals(PLAGIARISM_TURNITIN_SUBMIT_TO_INSTITUTIONAL_REPOSITORY, $response);
    }

    /**
     * Test that retrieve_successful_submissions returns rows that match the given
     * author, cmid and identifier when they have not been successfully submitted
     * or queued.
     */
    public function test_retrieve_successful_submissions_returns_matching_rows(): void {
        global $DB;
        $this->resetAfterTest();

        $cm   = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);
        $user = $this->getDataGenerator()->create_user();

        // Insert a row that should be returned (status = 'pending', not 'success' or 'queued').
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm'             => $cm->cmid,
            'userid'         => $user->id,
            'identifier'     => 'abc123',
            'statuscode'     => 'pending',
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'submitter'      => $user->id,
            'lastmodified'   => time(),
            'transmatch'     => 0,
        ]);

        $results = plagiarism_turnitin_retrieve_successful_submissions($user->id, $cm->cmid, 'abc123');

        $this->assertCount(1, $results);
    }

    /**
     * Test that retrieve_successful_submissions excludes rows with statuscode
     * 'success' or 'queued' — those have already been handled and should not
     * trigger a duplicate submission.
     */
    public function test_retrieve_successful_submissions_excludes_success_and_queued(): void {
        global $DB;
        $this->resetAfterTest();

        $cm   = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);
        $user = $this->getDataGenerator()->create_user();

        foreach (['success', 'queued'] as $status) {
            $DB->insert_record('plagiarism_turnitin_files', (object)[
                'cm'             => $cm->cmid,
                'userid'         => $user->id,
                'identifier'     => 'abc123',
                'statuscode'     => $status,
                'attempt'        => 0,
                'submissiontype' => 'file',
                'itemid'         => 0,
                'submitter'      => $user->id,
                'lastmodified'   => time(),
                'transmatch'     => 0,
            ]);
        }

        $results = plagiarism_turnitin_retrieve_successful_submissions($user->id, $cm->cmid, 'abc123');

        $this->assertCount(0, $results);
    }

    /**
     * Test that lock_anonymous_marking inserts a 'submitted' config record for
     * the given cmid the first time it is called.
     */
    public function test_lock_anonymous_marking_inserts_record(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        $this->assertFalse($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $cm->cmid, 'name' => 'submitted']
        ));

        plagiarism_turnitin_lock_anonymous_marking($cm->cmid);

        $this->assertTrue($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $cm->cmid, 'name' => 'submitted', 'value' => 1]
        ));
    }

    /**
     * Test that calling lock_anonymous_marking twice does not create a duplicate
     * record — the function is idempotent.
     */
    public function test_lock_anonymous_marking_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        plagiarism_turnitin_lock_anonymous_marking($cm->cmid);
        plagiarism_turnitin_lock_anonymous_marking($cm->cmid);

        $count = $DB->count_records('plagiarism_turnitin_config', ['cm' => $cm->cmid, 'name' => 'submitted']);
        $this->assertEquals(1, $count);
    }

    // Plagiarism_turnitin_is_eula_accepted tests.

    /**
     * Test that plagiarism_turnitin_is_eula_accepted returns true when the user
     * has accepted (user_agreement_accepted = 1).
     */
    public function test_is_eula_accepted_returns_true_when_accepted(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                  => $user->id,
            'turnitin_uid'            => 999,
            'user_agreement_accepted' => 1,
        ]);

        $this->assertTrue(plagiarism_turnitin_is_eula_accepted($user->id));
    }

    /**
     * Test that plagiarism_turnitin_is_eula_accepted returns false when not yet
     * accepted (user_agreement_accepted = 0).
     */
    public function test_is_eula_accepted_returns_false_when_not_accepted(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                  => $user->id,
            'turnitin_uid'            => 999,
            'user_agreement_accepted' => 0,
        ]);

        $this->assertFalse(plagiarism_turnitin_is_eula_accepted($user->id));
    }

    /**
     * Test that plagiarism_turnitin_is_eula_accepted returns false when the user
     * explicitly declined (user_agreement_accepted = -1).
     */
    public function test_is_eula_accepted_returns_false_when_declined(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                  => $user->id,
            'turnitin_uid'            => 999,
            'user_agreement_accepted' => -1,
        ]);

        $this->assertFalse(plagiarism_turnitin_is_eula_accepted($user->id));
    }

    /**
     * Test that plagiarism_turnitin_is_eula_accepted returns false when no record
     * exists in plagiarism_turnitin_users for the given user.
     */
    public function test_is_eula_accepted_returns_false_when_no_record(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(plagiarism_turnitin_is_eula_accepted($user->id));
    }

    // Plagiarism_turnitin_print_error tests.

    /**
     * Test that plagiarism_turnitin_print_error always throws a moodle_exception
     * with the input string as the errorcode.
     */
    public function test_print_error_throws_moodle_exception(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);

        plagiarism_turnitin_print_error('configureerror', 'plagiarism_turnitin', 'http://example.com');
    }

    /**
     * Test that the exception errorcode matches the $input argument so callers
     * can catch and identify specific error types.
     */
    public function test_print_error_exception_errorcode_matches_input(): void {
        $this->resetAfterTest();

        try {
            plagiarism_turnitin_print_error('configureerror', 'plagiarism_turnitin', 'http://example.com');
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('configureerror', $e->errorcode);
        }
    }

    /**
     * Test that when $module is null the raw $input string is used as the
     * exception message rather than being passed through get_string().
     */
    public function test_print_error_uses_raw_input_when_module_is_null(): void {
        $this->resetAfterTest();

        $rawmessage = 'Something went wrong: raw error text';
        try {
            plagiarism_turnitin_print_error($rawmessage, null, 'http://example.com');
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            // When $module is null, $input is used verbatim as the debug info ($e->a).
            $this->assertStringContainsString($rawmessage, $e->a);
        }
    }

    /**
     * Test that an explicitly provided $link is passed through to the exception
     * without modification.
     */
    public function test_print_error_uses_explicit_link(): void {
        $this->resetAfterTest();

        $link = 'https://my.moodle.example/mod/assign/view.php?id=42';
        try {
            plagiarism_turnitin_print_error('configureerror', 'plagiarism_turnitin', $link);
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals($link, $e->link);
        }
    }

    /**
     * Test that a non-lib.php $file causes the filename and line number to be
     * appended to the exception message, to aid debugging.
     */
    public function test_print_error_appends_file_and_line_for_non_lib_files(): void {
        $this->resetAfterTest();

        try {
            // Pass null as $module so $input is used verbatim — giving a predictable
            // message string that we can check the suffix was appended to.
            plagiarism_turnitin_print_error(
                'rawmessage',
                null,
                'http://example.com',
                null,
                '/some/path/myfile.php',
                42
            );
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            // The appended file/line lands in $e->a (the debug info passed to moodle_exception).
            $this->assertStringContainsString('myfile.php', $e->a);
            $this->assertStringContainsString('42', $e->a);
        }
    }

    /**
     * Test that when $file is lib.php the file/line suffix is NOT appended,
     * keeping the message clean for the most common caller.
     */
    public function test_print_error_does_not_append_file_for_lib_php(): void {
        $this->resetAfterTest();

        try {
            plagiarism_turnitin_print_error(
                'rawmessage',
                null,
                'http://example.com',
                null,
                '/some/path/lib.php',
                99
            );
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertStringNotContainsString('lib.php', $e->a);
            $this->assertStringNotContainsString('99', $e->a);
        }
    }
}

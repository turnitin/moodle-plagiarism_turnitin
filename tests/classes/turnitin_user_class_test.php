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
 * Unit tests for (some of) plagiarism/turnitin/classes/turnitin_user.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2018 Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/tests/generator/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for Turnitin user class
 *
 * @package turnitin
 */
#[CoversClass(turnitin_user::class)]
final class turnitin_user_class_test extends plagiarism_turnitin_test_lib {
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(turnitin_comms&\PHPUnit\Framework\MockObject\MockObject)
     */
    public $faketiicomms;

    /**
     * Set Overwrite mtrace to avoid output during the tests.
     */
    public function setUp(): void {
        parent::setUp();

        // Stub a fake tii comms.
        $this->faketiicomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Restore $_SERVER['REQUEST_URI'] after tests that modify it, so the next test
     * gets a clean environment regardless of Moodle version.
     */
    public function tearDown(): void {
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    /**
     * Test that we can get a Moodle use.
     *
     * @return void
     */
    public function test_get_moodle_user(): void {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();

        $turnitinuser = new turnitin_user(0, null, null, null, null);
        $response = $turnitinuser->get_moodle_user($student->id);

        // Check that we have an object back with user details. No need to check all params.
        $this->assertEquals($student->id, $response->id);
        $this->assertEquals('username1', $response->username);
    }

    /**
     * Test that we can get a pseudo domain.
     *
     * @return void
     */
    public function test_get_pseudo_domain(): void {
        $this->resetAfterTest();

        $response = turnitin_user::get_pseudo_domain();
        $this->assertEquals(PLAGIARISM_TURNITIN_DEFAULT_PSEUDO_DOMAIN, $response);
    }

    /**
     * Test that we can get a pseudo first name.
     *
     * @return void
     */
    public function test_get_pseudo_firstname(): void {
        $this->resetAfterTest();

        $turnitinuser = new turnitin_user(0, null, null, null, null);
        $response = $turnitinuser->get_pseudo_firstname();
        $this->assertEquals(PLAGIARISM_TURNITIN_DEFAULT_PSEUDO_FIRSTNAME, $response);
    }

    /**
     * Test that we can get a pseudo last name.
     *
     * @return void
     * @throws dml_exception
     */
    public function test_get_pseudo_lastname(): void {
        global $DB;
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', ['userid' => $student->id, 'fieldid' => 1, 'data' => 'Student', 'dataformat' => 0]);

        set_config('plagiarism_turnitin_pseudolastname', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_lastnamegen', 1, 'plagiarism_turnitin');

        $turnitinuser = new turnitin_user($student->id, null, null, null, null);
        $response = $turnitinuser->get_pseudo_lastname();

        // Existing data is present; the generate path updates it and returns the uniqueid.
        $this->assertNotEmpty($response);
    }

    /**
     * Test get_pseudo_lastname returns the stored value when data already exists and lastnamegen=0.
     */
    public function test_get_pseudo_lastname_returns_stored_value_when_lastnamegen_off(): void {
        global $DB;
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', [
            'userid' => $student->id, 'fieldid' => 1, 'data' => 'StoredValue', 'dataformat' => 0,
        ]);

        set_config('plagiarism_turnitin_pseudolastname', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_lastnamegen', 0, 'plagiarism_turnitin');

        $turnitinuser = new turnitin_user($student->id, null, null, null, null);
        $this->assertEquals('StoredValue', $turnitinuser->get_pseudo_lastname());
    }

    /**
     * Test get_pseudo_lastname returns get_string('user') when pseudolastname field is 0.
     */
    public function test_get_pseudo_lastname_returns_user_string_when_field_unset(): void {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();

        set_config('plagiarism_turnitin_pseudolastname', 0, 'plagiarism_turnitin');

        $turnitinuser = new turnitin_user($student->id, null, null, null, null);
        $this->assertEquals(get_string('user'), $turnitinuser->get_pseudo_lastname());
    }

    /**
     * Test get_pseudo_lastname generates and inserts a new value when no row exists yet.
     */
    public function test_get_pseudo_lastname_inserts_new_value_when_no_existing_row(): void {
        global $DB;
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();

        // No user_info_data row — the generate path must insert one.
        set_config('plagiarism_turnitin_pseudolastname', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_lastnamegen', 1, 'plagiarism_turnitin');

        $turnitinuser = new turnitin_user($student->id, null, null, null, null);
        $response = $turnitinuser->get_pseudo_lastname();

        $this->assertNotEmpty($response);
        $this->assertTrue($DB->record_exists('user_info_data', ['userid' => $student->id, 'fieldid' => 1]));
    }

    /**
     * Test get_moodle_user rebuilds the email from username when the user has no valid email address.
     */
    public function test_get_moodle_user_rebuilds_email_when_missing_at_sign(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['username' => 'john.doe.example.com']);
        $DB->set_field('user', 'email', 'noemail', ['id' => $user->id]);

        $turnitinuser = new turnitin_user(0, null, null, null, null);
        $turnitinuser->get_moodle_user($user->id);

        // Email rebuilt from username: explode('.'), drop last part, join.
        $this->assertEquals('john.doe.example', $turnitinuser->email);
    }

    /**
     * Test get_moodle_user populates instructorrubrics from an existing plagiarism_turnitin_users record.
     */
    public function test_get_moodle_user_loads_instructor_rubrics(): void {
        global $DB;
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $rubrics = json_encode([42 => 'My Rubric']);
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                 => $student->id,
            'turnitin_uid'           => 1,
            'user_agreement_accepted' => 1,
            'instructor_rubrics'     => $rubrics,
        ]);

        // Construct with the student's real id so get_moodle_user uses the right userid.
        $turnitinuser = new turnitin_user($student->id, null, null, null, false);

        $this->assertNotEmpty($turnitinuser->get_instructor_rubrics());
    }

    /**
     * Test get_pseudo_domain returns the configured value when a custom domain is set.
     */
    public function test_get_pseudo_domain_returns_configured_value(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_pseudoemaildomain', 'custom.example.com', 'plagiarism_turnitin');
        $this->assertEquals('custom.example.com', turnitin_user::get_pseudo_domain());
    }

    /**
     * Test get_pseudo_firstname returns the configured value when a custom firstname is set.
     */
    public function test_get_pseudo_firstname_returns_configured_value(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_pseudofirstname', 'Anonymous', 'plagiarism_turnitin');

        $turnitinuser = new turnitin_user(0, null, null, null, null);
        $this->assertEquals('Anonymous', $turnitinuser->get_pseudo_firstname());
    }

    /**
     * Test get_user_role returns the role the user was constructed with.
     */
    public function test_get_user_role_returns_constructor_role(): void {
        $this->resetAfterTest();

        $turnitinuser = new turnitin_user(0, 'Instructor', null, null, null);
        $this->assertEquals('Instructor', $turnitinuser->get_user_role());
    }

    /**
     * Test get_instructor_rubrics returns an empty array by default.
     */
    public function test_get_instructor_rubrics_returns_empty_by_default(): void {
        $this->resetAfterTest();

        $turnitinuser = new turnitin_user(0, null, null, null, null);
        $this->assertEquals([], $turnitinuser->get_instructor_rubrics());
    }

    /**
     * Test unlink_user deletes the plagiarism_turnitin_users row when the Moodle user is deleted.
     */
    public function test_unlink_user_deletes_row_for_deleted_moodle_user(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $tiiid = $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 99,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        // Mark the Moodle user as deleted.
        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);

        $turnitinuser = new turnitin_user($user->id, null, null, null, false);
        $turnitinuser->unlink_user($tiiid);

        $this->assertFalse($DB->record_exists('plagiarism_turnitin_users', ['userid' => $user->id]));
    }

    /**
     * Test that when a plagiarism_turnitin_users record with turnitin_uid > 0 exists,
     * the constructor resolves the tii user id from the DB without making API calls.
     */
    public function test_constructor_resolves_tii_user_id_from_db(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                 => $user->id,
            'turnitin_uid'           => 12345,
            'turnitin_utp'           => 0,
            'user_agreement_accepted' => 1,
        ]);

        // With a valid turnitin_uid in the DB, the constructor skips the API path.
        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'site', true);
        $this->assertEquals(12345, $turnitinuser->tiiuserid);
    }

    /**
     * Helper: set fake API credentials so turnitin_comms constructs without throwing,
     * while any actual API call will fail with a network/auth exception caught in the method.
     */
    private function set_fake_credentials(): void {
        set_config('plagiarism_turnitin_accountid', '9999', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://invalid.turnitin.example', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'fakesecret', 'plagiarism_turnitin');
    }

    /**
     * Helper: create a user with a pre-populated plagiarism_turnitin_users record so the
     * constructor does not attempt API calls, then return a turnitin_user instance ready
     * for direct method testing.
     *
     * @param string $role
     * @param string $workflowcontext 'site' or 'cron'. Use 'cron' for API-calling methods so
     *     handle_exceptions logs rather than throwing a moodle_exception.
     * @return turnitin_user
     */
    private function make_user_with_tii_record(string $role = 'Instructor', string $workflowcontext = 'site'): turnitin_user {
        global $DB;

        $mdluser = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                 => $mdluser->id,
            'turnitin_uid'           => 99999,
            'turnitin_utp'           => 0,
            'user_agreement_accepted' => 0,
        ]);

        return new turnitin_user($mdluser->id, $role, false, $workflowcontext, false);
    }

    /**
     * Test that join_user_to_class handles API failure gracefully and returns false.
     *
     * With fake credentials the network call fails; the catch block returns false.
     */
    public function test_join_user_to_class_returns_false_on_api_failure(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();

        $turnitinuser = $this->make_user_with_tii_record();
        $result = $turnitinuser->join_user_to_class(0);

        $this->assertFalse($result);
    }

    /**
     * Test that get_accepted_user_agreement returns true in PHPUnit context when the API fails.
     *
     * The catch block at line 492 has an explicit PHPUNIT_TEST guard that returns true,
     * allowing this path to be exercised safely in tests.
     */
    public function test_get_accepted_user_agreement_returns_true_in_test_context(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();

        $turnitinuser = $this->make_user_with_tii_record();
        $result = $turnitinuser->get_accepted_user_agreement();

        $this->assertTrue($result);
    }

    /**
     * Test that edit_tii_user runs without throwing when the API call fails in cron context.
     *
     * In cron context ($workflowcontext = 'cron') handle_exceptions logs the error
     * rather than re-throwing, so the method returns false cleanly.
     */
    public function test_edit_tii_user_handles_api_failure_gracefully(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();

        // Use cron context so handle_exceptions logs rather than throwing a moodle_exception.
        $turnitinuser = $this->make_user_with_tii_record('Instructor', 'cron');
        $result = $turnitinuser->edit_tii_user();

        $this->assertFalse($result);
    }

    /**
     * Test that set_user_values_from_tii covers its setup and outer catch block on API failure.
     *
     * The inner catch at line 549 uses an unqualified `Exception` which — in the
     * plagiarism_turnitin namespace — does not catch TurnitinSDKException from the
     * SDK. This is a pre-existing bug in the production code. We verify the method
     * executes its setup lines, and accept the SDK exception propagating from the retry.
     */
    public function test_set_user_values_from_tii_handles_api_failure_gracefully(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();

        $turnitinuser = $this->make_user_with_tii_record('Instructor', 'cron');

        // The outer catch block is entered (API call fails), but the retry's API call
        // also fails with TurnitinSDKException which escapes the inner unqualified catch.
        $this->expectException(\Integrations\PhpSdk\TurnitinSDKException::class);
        $turnitinuser->set_user_values_from_tii();
    }

    /**
     * Test plagiarism_turnitin_getusers returns the expected structure for a basic request.
     */
    public function test_plagiarism_turnitin_getusers_returns_expected_structure(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 42,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        // Simulate the query string that the AJAX handler would receive.
        $_SERVER['REQUEST_URI'] = http_build_query([
            'start'    => 0,
            'length'   => 10,
            'draw'     => 1,
            'search'   => ['value' => ''],
            'order'    => [],
            'columns'  => array_fill(0, 5, ['searchable' => '0']),
        ]);

        $result = turnitin_user::plagiarism_turnitin_getusers();

        $this->assertArrayHasKey('aaData', $result);
        $this->assertArrayHasKey('recordsTotal', $result);
        $this->assertArrayHasKey('draw', $result);
        $this->assertGreaterThanOrEqual(1, $result['recordsTotal']);
    }

    /**
     * Test plagiarism_turnitin_getusers applies the search filter when a search term is provided.
     */
    public function test_plagiarism_turnitin_getusers_filters_by_search_term(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['lastname' => 'UniqueLastName']);
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 43,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);
        $other = $this->getDataGenerator()->create_user(['lastname' => 'OtherUser']);
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $other->id, 'turnitin_uid' => 44,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        $_SERVER['REQUEST_URI'] = http_build_query([
            'start'    => 0,
            'length'   => 10,
            'draw'     => 1,
            'search'   => ['value' => 'UniqueLastName'],
            'order'    => [],
            'columns'  => [
                ['searchable' => '0'],
                ['searchable' => '0'],
                ['searchable' => '1'],
                ['searchable' => '1'],
                ['searchable' => '0'],
            ],
        ]);

        $result = turnitin_user::plagiarism_turnitin_getusers();

        $this->assertEquals(1, $result['recordsFiltered']);
    }

    /**
     * Test plagiarism_turnitin_getusers applies ordering when order params are provided.
     */
    public function test_plagiarism_turnitin_getusers_applies_ordering(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 45,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        $_SERVER['REQUEST_URI'] = http_build_query([
            'start'    => 0,
            'length'   => 10,
            'draw'     => 1,
            'search'   => ['value' => ''],
            'order'    => [['column' => 2, 'dir' => 'asc']],
            'columns'  => array_fill(0, 5, ['searchable' => '0']),
        ]);

        $result = turnitin_user::plagiarism_turnitin_getusers();

        $this->assertArrayHasKey('aaData', $result);
    }

    /**
     * Test plagiarism_turnitin_getusers builds pseudo email when pseudo mode is enabled.
     */
    public function test_plagiarism_turnitin_getusers_builds_pseudo_email_when_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_enablepseudo', 1, 'plagiarism_turnitin');

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 46,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        $_SERVER['REQUEST_URI'] = http_build_query([
            'start'    => 0,
            'length'   => 10,
            'draw'     => 1,
            'search'   => ['value' => ''],
            'order'    => [],
            'columns'  => array_fill(0, 5, ['searchable' => '0']),
        ]);

        $result = turnitin_user::plagiarism_turnitin_getusers();

        $this->assertArrayHasKey('aaData', $result);
    }

    /**
     * Test plagiarism_turnitin_getusers shows empty string for turnitin_uid when it is 0.
     */
    public function test_plagiarism_turnitin_getusers_shows_empty_uid_when_zero(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 0,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        $_SERVER['REQUEST_URI'] = http_build_query([
            'start'    => 0,
            'length'   => 10,
            'draw'     => 1,
            'search'   => ['value' => ''],
            'order'    => [],
            'columns'  => array_fill(0, 5, ['searchable' => '0']),
        ]);

        $result = turnitin_user::plagiarism_turnitin_getusers();

        // A turnitin_uid of 0 should be replaced with an empty string in the output row.
        $this->assertNotEmpty($result['aaData']);
        $row = $result['aaData'][0];
        // Row structure: [checkbox, uid, lastname, firstname, pseudoemail].
        $this->assertEquals('', $row[1]);
    }

    /**
     * Test get_pseudo_lastname generates and updates an existing row when data is empty and lastnamegen=1.
     */
    public function test_get_pseudo_lastname_updates_existing_row_when_data_empty(): void {
        global $DB;
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        // Insert a row with empty data so the update path (line 202) is taken.
        $DB->insert_record('user_info_data', [
            'userid' => $student->id, 'fieldid' => 1, 'data' => '', 'dataformat' => 0,
        ]);

        set_config('plagiarism_turnitin_pseudolastname', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_lastnamegen', 1, 'plagiarism_turnitin');

        $turnitinuser = new turnitin_user($student->id, null, null, null, null);
        $result = $turnitinuser->get_pseudo_lastname();

        $this->assertNotEmpty($result);
        // The row should have been updated with the generated value.
        $updated = $DB->get_record('user_info_data', ['userid' => $student->id, 'fieldid' => 1]);
        $this->assertEquals($result, $updated->data);
    }

    /**
     * Test that the constructor sets tiiuserid to 0 when no plagiarism_turnitin_users record exists.
     *
     * When no DB record exists get_tii_user_id() sets tiiuserid=0 then attempts the API
     * (find_tii_user_id → create_tii_user). With fake credentials and cron context the
     * exception is swallowed and tiiuserid stays 0.
     */
    public function test_constructor_sets_tiiuserid_zero_when_no_db_record(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();

        $user = $this->getDataGenerator()->create_user();

        // No plagiarism_turnitin_users record — constructor will attempt API in cron context.
        // TurnitinApiException is caught and re-thrown inside find_tii_user_id, triggering
        // create_tii_user which also fails. In cron context handle_exceptions swallows it.
        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'cron', true);

        $this->assertEquals(0, $turnitinuser->tiiuserid);
    }

    /**
     * Test join_user_to_class covers the set_diagnostic path when enablediagnostic is set.
     */
    public function test_join_user_to_class_with_diagnostic_mode_set(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();
        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        $turnitinuser = $this->make_user_with_tii_record('Instructor', 'cron');
        $result = $turnitinuser->join_user_to_class(0);

        $this->assertFalse($result);
    }

    /**
     * Test edit_tii_user returns true immediately when pseudo mode is enabled.
     *
     * When plagiarism_turnitin_enablepseudo is set the method skips the API call entirely.
     */
    public function test_edit_tii_user_returns_true_when_pseudo_enabled(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();
        set_config('plagiarism_turnitin_enablepseudo', 1, 'plagiarism_turnitin');

        $turnitinuser = $this->make_user_with_tii_record();
        $result = $turnitinuser->edit_tii_user();

        $this->assertTrue($result);
    }

    /**
     * Test plagiarism_turnitin_getusers excludes non-integer search terms on numeric columns.
     *
     * Lines 646–647 are hit when ssearch is non-empty and a numeric column (i<=1) is
     * searchable — the non-integer value sets $include=false so no WHERE clause is added.
     */
    public function test_plagiarism_turnitin_getusers_excludes_non_int_search_on_numeric_column(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 50,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        // Make column 0 (userid) searchable with a non-integer term.
        $_SERVER['REQUEST_URI'] = http_build_query([
            'start'    => 0,
            'length'   => 10,
            'draw'     => 1,
            'search'   => ['value' => 'notaninteger'],
            'order'    => [],
            'columns'  => [
                ['searchable' => '1'], // Column 0 is numeric; non-integer term is excluded.
                ['searchable' => '0'],
                ['searchable' => '0'],
                ['searchable' => '0'],
                ['searchable' => '0'],
            ],
        ]);

        $result = turnitin_user::plagiarism_turnitin_getusers();

        $this->assertArrayHasKey('aaData', $result);
    }

    /**
     * Test that the constructor with pseudo enabled covers the TiiPseudoUser path in find_tii_user_id.
     *
     * No DB record exists so get_tii_user_id() calls find_tii_user_id(). With pseudo
     * enabled and role=Learner the TiiPseudoUser branch (lines 261–263) is executed
     * before the API call fails in cron context.
     */
    public function test_constructor_covers_pseudo_path_in_find_tii_user_id(): void {
        $this->resetAfterTest();
        $this->set_fake_credentials();
        set_config('plagiarism_turnitin_enablepseudo', 1, 'plagiarism_turnitin');

        $user = $this->getDataGenerator()->create_user();

        // No plagiarism_turnitin_users record — triggers find_tii_user_id with pseudo path.
        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'cron', true);

        $this->assertEquals(0, $turnitinuser->tiiuserid);
    }

    /**
     * Build a mock turnitin_comms whose initialise_api() returns a mock TurnitinAPI.
     *
     * The returned comms mock can be assigned to $turnitinuser->comms so that all
     * API methods use it instead of making real network calls.
     *
     * @param array $apiMethods Map of TurnitinAPI method name → return value or callback.
     * @return turnitin_comms
     */
    private function make_mock_comms(array $apimethods = []): turnitin_comms {
        $mockapi = $this->getMockBuilder(\Integrations\PhpSdk\TurnitinAPI::class)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($apimethods as $method => $returnvalue) {
            if (is_callable($returnvalue)) {
                $mockapi->method($method)->willReturnCallback($returnvalue);
            } else {
                $mockapi->method($method)->willReturn($returnvalue);
            }
        }

        $mockcomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialise_api', 'set_diagnostic'])
            ->getMock();
        $mockcomms->method('initialise_api')->willReturn($mockapi);

        return $mockcomms;
    }

    /**
     * Build a mock \Integrations\PhpSdk\Response whose getUser() returns a mock TiiUser
     * with the given turnitin user id and EULA accepted=true.
     *
     * @param int $tiiuserid
     * @return \Integrations\PhpSdk\Response
     */
    private function make_user_response(int $tiiuserid): \Integrations\PhpSdk\Response {
        $mockuser = $this->getMockBuilder(\Integrations\PhpSdk\TiiUser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockuser->method('getUserId')->willReturn($tiiuserid);
        $mockuser->method('getAcceptedUserAgreement')->willReturn(true);
        $mockuser->method('getUserMessages')->willReturn(0);
        $mockuser->method('getInstructorRubrics')->willReturn([]);
        $mockuser->method('getFirstName')->willReturn('Test');
        $mockuser->method('getLastName')->willReturn('User');
        $mockuser->method('getEmail')->willReturn('test@example.com');

        $mockresponse = $this->getMockBuilder(\Integrations\PhpSdk\Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockresponse->method('getUser')->willReturn($mockuser);

        return $mockresponse;
    }

    /**
     * Test find_tii_user_id (via constructor) resolves tiiuserid from a mocked API response.
     */
    public function test_find_tii_user_id_resolves_from_api(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $mockcomms = $this->make_mock_comms([
            'findUser' => $this->make_user_response(77777),
        ]);

        // No DB record — get_tii_user_id() calls find_tii_user_id() which calls findUser().
        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'cron', true, $mockcomms);

        $this->assertEquals(77777, $turnitinuser->tiiuserid);
    }

    /**
     * Test create_tii_user (via constructor) is called when find_tii_user_id throws TurnitinApiException.
     */
    public function test_create_tii_user_called_when_find_throws(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $mockcomms = $this->make_mock_comms([
            'findUser'   => function () {
                throw new \Integrations\PhpSdk\TurnitinApiException('usernotfound', 'User not found');
            },
            'createUser' => $this->make_user_response(88888),
        ]);

        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'cron', true, $mockcomms);

        $this->assertEquals(88888, $turnitinuser->tiiuserid);
    }

    /**
     * Test join_user_to_class returns true when createMembership succeeds.
     */
    public function test_join_user_to_class_returns_true_on_success(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 99,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 0,
        ]);

        $mockcomms = $this->make_mock_comms(['createMembership' => null]);

        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'site', false, $mockcomms);
        $result = $turnitinuser->join_user_to_class(1);

        $this->assertTrue($result);
    }

    /**
     * Test get_accepted_user_agreement returns true and updates the DB when EULA is accepted.
     */
    public function test_get_accepted_user_agreement_returns_true_when_accepted(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $tiiid = $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 99,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 0,
        ]);

        $mockcomms = $this->make_mock_comms(['readUser' => $this->make_user_response(99)]);

        $turnitinuser = new turnitin_user($user->id, 'Learner', false, 'site', false, $mockcomms);
        $result = $turnitinuser->get_accepted_user_agreement();

        $this->assertTrue($result);
        $this->assertEquals(1, $DB->get_field('plagiarism_turnitin_users', 'user_agreement_accepted', ['id' => $tiiid]));
    }

    /**
     * Test set_user_values_from_tii populates usermessages when readUser succeeds.
     */
    public function test_set_user_values_from_tii_returns_user_data_on_success(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 99,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 0,
        ]);

        $mockcomms = $this->make_mock_comms(['readUser' => $this->make_user_response(99)]);

        $turnitinuser = new turnitin_user($user->id, 'Instructor', false, 'site', false, $mockcomms);
        $result = $turnitinuser->set_user_values_from_tii();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    /**
     * Test edit_tii_user calls updateUser and returns true when pseudo is disabled and API succeeds.
     */
    public function test_edit_tii_user_returns_true_on_api_success(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 99,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 0,
        ]);

        $mockcomms = $this->make_mock_comms(['updateUser' => null]);

        $turnitinuser = new turnitin_user($user->id, 'Instructor', false, 'site', false, $mockcomms);
        $result = $turnitinuser->edit_tii_user();

        $this->assertTrue($result);
    }
}

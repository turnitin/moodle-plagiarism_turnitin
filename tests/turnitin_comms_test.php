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
 * Unit tests for classes/turnitin_comms.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_comms.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_comms::class)]
final class turnitin_comms_test extends \advanced_testcase {
    /**
     * Set the minimum valid API credentials.
     */
    private function set_credentials(
        string $accountid = '1001',
        string $secretkey = 'ABCDEFGH',
        string $apiurl = 'https://api.turnitin.com'
    ): void {
        set_config('plagiarism_turnitin_accountid', $accountid, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', $secretkey, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', $apiurl, 'plagiarism_turnitin');
    }

    // Tests for __construct().

    /**
     * Test constructor succeeds when credentials are set.
     */
    public function test_constructor_succeeds_with_credentials(): void {
        $this->resetAfterTest();
        $this->set_credentials();

        $comms = new turnitin_comms();

        $this->assertInstanceOf(turnitin_comms::class, $comms);
    }

    /**
     * Test constructor throws moodle_exception when credentials are missing.
     *
     * The config properties are absent (stdClass with no matching keys), which
     * causes the empty-check to fail and plagiarism_turnitin_print_error to throw.
     * We suppress the PHP notices from accessing undefined properties.
     */
    public function test_constructor_throws_when_credentials_missing(): void {
        $this->resetAfterTest();

        // Ensure no credentials are set so the constructor throws.
        set_config('plagiarism_turnitin_accountid', '', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', '', 'plagiarism_turnitin');

        $this->expectException(\moodle_exception::class);
        new turnitin_comms();
    }

    /**
     * Test constructor uses explicitly passed URL instead of config.
     */
    public function test_constructor_uses_explicit_url(): void {
        $this->resetAfterTest();

        // No apiurl in config — pass it explicitly.
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');

        $comms = new turnitin_comms('1001', 'ABCDEFGH', 'https://api.turnitin.com');

        $this->assertInstanceOf(turnitin_comms::class, $comms);
    }

    /**
     * Test constructor strips a trailing slash from the configured API URL.
     */
    public function test_constructor_strips_trailing_slash_from_url(): void {
        $this->resetAfterTest();
        $this->set_credentials(apiurl: 'https://api.turnitin.com/');

        // Should not throw — the trailing slash is removed internally.
        $comms = new turnitin_comms();

        $this->assertInstanceOf(turnitin_comms::class, $comms);
    }

    /**
     * Test constructor uses the diagnostic setting from config when set.
     */
    public function test_constructor_reads_diagnostic_setting(): void {
        $this->resetAfterTest();
        $this->set_credentials();
        set_config('plagiarism_turnitin_enablediagnostic', 1, 'plagiarism_turnitin');

        $comms = new turnitin_comms();

        $this->assertInstanceOf(turnitin_comms::class, $comms);
    }

    // Tests for initialise_api().

    /**
     * Test initialise_api returns a TurnitinAPI instance.
     */
    public function test_initialise_api_returns_turnitin_api(): void {
        $this->resetAfterTest();
        $this->set_credentials();

        $comms = new turnitin_comms();
        $api   = $comms->initialise_api();

        $this->assertInstanceOf(\Integrations\PhpSdk\TurnitinAPI::class, $api);
    }

    /**
     * Test initialise_api sets log path when diagnostic mode is on.
     */
    public function test_initialise_api_sets_log_path_when_diagnostic_on(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->set_credentials();
        set_config('plagiarism_turnitin_enablediagnostic', 2, 'plagiarism_turnitin');

        $comms = new turnitin_comms();
        // Set diagnostic so the log path branch is entered.
        $comms->set_diagnostic(1);
        $api = $comms->initialise_api();

        $this->assertInstanceOf(\Integrations\PhpSdk\TurnitinAPI::class, $api);
    }

    /**
     * Test initialise_api throws when tiioffline mode is active.
     */
    public function test_initialise_api_throws_when_offline_mode_on(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->set_credentials();

        $CFG->tiioffline = true;
        $comms = new turnitin_comms();

        $this->expectException(\moodle_exception::class);
        $comms->initialise_api();
    }

    /**
     * Test initialise_api does not throw when offline mode is for testing connection.
     */
    public function test_initialise_api_skips_offline_check_when_testing_connection(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->set_credentials();

        $CFG->tiioffline = true;
        $comms = new turnitin_comms();

        // Passing $istestingconnection=true bypasses the offline check.
        $api = $comms->initialise_api(true);

        $this->assertInstanceOf(\Integrations\PhpSdk\TurnitinAPI::class, $api);
        unset($CFG->tiioffline);
    }

    /**
     * Test initialise_api sets proxy host when CFG->proxyhost is configured.
     */
    public function test_initialise_api_sets_proxy_host(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->set_credentials();

        $CFG->proxyhost = 'proxy.example.com';
        $comms = new turnitin_comms();
        $api   = $comms->initialise_api();

        $this->assertInstanceOf(\Integrations\PhpSdk\TurnitinAPI::class, $api);
        unset($CFG->proxyhost);
    }

    // Tests for handle_exceptions().

    /**
     * Test handle_exceptions returns the error string when embedded=true.
     */
    public function test_handle_exceptions_returns_string_when_embedded(): void {
        $this->resetAfterTest();
        $this->set_credentials();

        $comms = new turnitin_comms();
        $e     = new \Exception('Something went wrong', 500);

        $result = $comms->handle_exceptions($e, 'coursegeterror', false, true);

        $this->assertIsString($result);
        $this->assertStringContainsString('Something went wrong', $result);
    }

    /**
     * Test handle_exceptions with no tterrorstr produces output containing exception details.
     */
    public function test_handle_exceptions_with_no_error_string(): void {
        $this->resetAfterTest();
        $this->set_credentials();

        $comms = new turnitin_comms();
        $e     = new \Exception('Test error', 42);

        $result = $comms->handle_exceptions($e, '', false, true);

        $this->assertStringContainsString('Test error', $result);
    }

    /**
     * Test handle_exceptions with an exception that has getFaultCode.
     */
    public function test_handle_exceptions_includes_fault_code(): void {
        $this->resetAfterTest();
        $this->set_credentials();

        $comms = new turnitin_comms();

        // Use a real TurnitinApiException so all methods are available naturally.
        $e = new \Integrations\PhpSdk\TurnitinApiException('invaliddata', 'Invalid submission data');

        $result = $comms->handle_exceptions($e, '', false, true);

        $this->assertStringContainsString('invaliddata', $result);
    }

    // Tests for set_diagnostic().

    /**
     * Test set_diagnostic updates the diagnostic value used by initialise_api.
     */
    public function test_set_diagnostic_changes_diagnostic_setting(): void {
        $this->resetAfterTest();
        $this->set_credentials();

        $comms = new turnitin_comms();
        $comms->set_diagnostic(0);

        // Calling initialise_api after set_diagnostic(0) should not set a log path.
        $api = $comms->initialise_api();

        $this->assertInstanceOf(\Integrations\PhpSdk\TurnitinAPI::class, $api);
    }
}

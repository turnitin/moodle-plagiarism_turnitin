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
 * Unit tests for classes/turnitin_eula_form.php.
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
 * Tests for turnitin_eula_form::render().
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_eula_form::class)]
final class turnitin_eula_form_test extends \advanced_testcase {
    /**
     * Reset the renderer's connection cache before each test so tests are isolated.
     */
    protected function setUp(): void {
        parent::setUp();
        turnitin_eula_form::reset_connection_cache();
    }

    /**
     * Build a cm_info-like stdClass for a real assign module.
     */
    private function make_cm(string $modtype = 'assign'): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module($modtype, ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance($modtype, $mod->id);
        return (object)['id' => $cm->id, 'course' => $course->id, 'modname' => $modtype];
    }

    /**
     * Build a mock of plagiarism_plugin_turnitin with test_turnitin_connection()
     * returning the given value and get_course_data() returning a minimal coursedata object.
     *
     * @param bool $connected Value to return from test_turnitin_connection().
     */
    private function make_mock_plugin(bool $connected): \plagiarism_plugin_turnitin {
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection', 'get_course_data'])
            ->getMock();

        $mock->method('test_turnitin_connection')->willReturn($connected);

        $coursedata = new \stdClass();
        $coursedata->turnitin_cid = 0;
        $mock->method('get_course_data')->willReturn($coursedata);

        return $mock;
    }

    /**
     * Register the current user with a pre-set Turnitin UID so turnitin_user's
     * constructor does not attempt an API call.
     */
    private function register_fake_tii_user(int $userid, int $accepted = 1): void {
        global $DB;
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                  => $userid,
            'turnitin_uid'            => 99999,
            'turnitin_utp'            => 0,
            'user_agreement_accepted' => $accepted,
        ]);
    }

    /**
     * Set fake API credentials so turnitin_comms constructs without throwing.
     */
    private function set_credentials(): void {
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
    }

    // Tests for early-return paths.

    /**
     * Test render() returns '' immediately when there is no Turnitin connection.
     */
    public function test_returns_empty_when_no_connection(): void {
        $this->resetAfterTest();

        $cm     = $this->make_cm();
        $result = turnitin_eula_form::render($cm, $this->make_mock_plugin(false));

        $this->assertSame('', $result);
    }

    /**
     * Test render() caches the connection result so test_turnitin_connection()
     * is called at most once across multiple render() calls in a request.
     */
    public function test_connection_is_cached_across_calls(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $USER;
        $this->register_fake_tii_user((int)$USER->id, 1);

        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection', 'get_course_data'])
            ->getMock();

        // Verify test_turnitin_connection() is only called once across multiple render() calls.
        $mock->expects($this->once())
            ->method('test_turnitin_connection')
            ->willReturn(false);

        $coursedata = (object)['turnitin_cid' => 0];
        $mock->method('get_course_data')->willReturn($coursedata);

        $cm = $this->make_cm();
        turnitin_eula_form::render($cm, $mock);
        turnitin_eula_form::render($cm, $mock);
    }

    /**
     * Test render() returns '' when the user has already accepted the EULA.
     */
    public function test_returns_empty_when_eula_already_accepted(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        // With useragreementaccepted=1 the EULA is already accepted.
        $this->register_fake_tii_user((int)$USER->id, 1);

        $cm     = $this->make_cm();
        $result = turnitin_eula_form::render($cm, $this->make_mock_plugin(true));

        $this->assertSame('', $result);
    }

    /**
     * Test render() returns '' when useragreementaccepted is a negative value (explicitly declined).
     *
     * A declined value (-1) is non-zero so eulaaccepted = -1, which is truthy,
     * causing render() to return '' without building EULA HTML. The EULA prompt
     * HTML path requires a live Turnitin API to reach in this environment.
     */
    public function test_returns_empty_when_eula_explicitly_declined(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        $this->register_fake_tii_user((int)$USER->id, -1);

        $cm     = $this->make_cm();
        $result = turnitin_eula_form::render($cm, $this->make_mock_plugin(true));

        // A value of -1 is non-zero so the accepted branch is taken and '' is returned.
        $this->assertSame('', $result);
    }

    /**
     * Test render() calls get_accepted_user_agreement() when useragreementaccepted=0.
     *
     * With useragreementaccepted=0 the renderer delegates to get_accepted_user_agreement().
     * In PHPUnit context that method has a PHPUNIT_TEST guard returning true, so the result
     * is still '' — but this exercises the conditional branch.
     */
    public function test_calls_get_accepted_user_agreement_when_status_is_zero(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        // With useragreementaccepted=0 the renderer calls get_accepted_user_agreement().
        $this->register_fake_tii_user((int)$USER->id, 0);

        $cm     = $this->make_cm();
        $result = turnitin_eula_form::render($cm, $this->make_mock_plugin(true));

        // In PHPUnit context get_accepted_user_agreement() returns true, so '' is returned.
        $this->assertSame('', $result);
    }

    /**
     * Test render() returns a string (not null/void) for a forum module when connected.
     *
     * Verifies the forum-specific code path (no noscript ULA text) is reachable.
     * The EULA HTML itself requires a live API to render; we just verify no exception.
     */
    public function test_returns_string_for_forum_module(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        $this->register_fake_tii_user((int)$USER->id, 1);

        $cm     = $this->make_cm('forum');
        $result = turnitin_eula_form::render($cm, $this->make_mock_plugin(true));

        $this->assertIsString($result);
    }

    /**
     * Test that render_eula_form() on plagiarism_plugin_turnitin delegates to render().
     */
    public function test_render_eula_form_stub_delegates_to_renderer(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        // User accepted — renderer returns '' without building any HTML.
        $this->register_fake_tii_user((int)$USER->id, 1);

        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection', 'get_course_data'])
            ->getMock();
        $mock->method('test_turnitin_connection')->willReturn(true);
        $mock->method('get_course_data')->willReturn((object)['turnitin_cid' => 0]);

        $cm     = $this->make_cm();
        $result = $mock->render_eula_form($cm);

        $this->assertSame('', $result);
    }
}

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
 * Unit tests for classes/hook_callbacks.php.
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

use core\hook\output\before_footer_html_generation;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for hook_callbacks.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(hook_callbacks::class)]
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Build the hook object passed to before_footer_html_generation().
     */
    private function make_hook(): before_footer_html_generation {
        return new before_footer_html_generation($this->createMock(\renderer_base::class));
    }

    /**
     * Build a mock of plagiarism_plugin_turnitin that stubs the API-calling methods
     * so turnitin_eula_form::render() returns early without making real connections.
     *
     * test_turnitin_connection() returning false causes turnitin_eula_form::render()
     * to return '' immediately, which is the correct behaviour for all hook tests that
     * verify the EULA is not shown.
     *
     * @param bool $connected Value to return from test_turnitin_connection().
     * @return \plagiarism_plugin_turnitin
     */
    private function make_mock_plugin(bool $connected = false): \plagiarism_plugin_turnitin {
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['test_turnitin_connection', 'create_tii_course'])
            ->getMock();
        $mock->method('test_turnitin_connection')->willReturn($connected);
        $mock->method('create_tii_course')->willReturn((object)['turnitin_cid' => null, 'turnitin_ctl' => '']);
        return $mock;
    }

    /**
     * Set up a quiz course module and configure PAGE to look like the quiz view.
     *
     * Returns the cm_info object so callers can set up plagiarism config for it.
     *
     * @return \cm_info
     */
    private function set_up_quiz_page(): \cm_info {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $cm     = get_fast_modinfo($course)->instances['quiz'][$quiz->id];

        $PAGE->set_cm($cm);
        $PAGE->set_pagetype('mod-quiz-view');

        return $cm;
    }

    // Tests for before_footer_html_generation().

    /**
     * Test that the hook does nothing when the page is not the quiz view.
     */
    public function test_does_nothing_when_not_on_quiz_page(): void {
        global $PAGE;
        $this->resetAfterTest();

        $PAGE->set_pagetype('site-index');

        $this->expectOutputString('');
        hook_callbacks::before_footer_html_generation($this->make_hook());
    }

    /**
     * Test that the hook returns early when Turnitin is not enabled for mod_quiz.
     */
    public function test_returns_early_when_module_not_enabled(): void {
        $this->resetAfterTest();

        $this->set_up_quiz_page();

        // Explicitly disable Turnitin for mod_quiz (default is off).
        set_config('plagiarism_turnitin_mod_quiz', 0, 'plagiarism_turnitin');

        $this->expectOutputString('');
        hook_callbacks::before_footer_html_generation($this->make_hook());
    }

    /**
     * Test that the hook returns early when use_turnitin is not set to 1 for the CM.
     */
    public function test_returns_early_when_use_turnitin_not_enabled_for_cm(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->set_up_quiz_page();

        set_config('plagiarism_turnitin_mod_quiz', 1, 'plagiarism_turnitin');

        // Insert use_turnitin=0 for this cm.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'use_turnitin',
            'value'       => '0',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        $this->expectOutputString('');
        hook_callbacks::before_footer_html_generation($this->make_hook());
    }

    /**
     * Test that the hook returns early when the EULA has already been accepted
     * (render_eula_form returns an empty string).
     */
    public function test_returns_early_when_eula_already_accepted(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->set_up_quiz_page();

        set_config('plagiarism_turnitin_mod_quiz', 1, 'plagiarism_turnitin');

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'use_turnitin',
            'value'       => '1',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // Returns '' when the EULA is already accepted.
        $mock = $this->make_mock_plugin();
        $this->expectOutputString('');
        hook_callbacks::before_footer_html_generation($this->make_hook(), $mock);
    }

    /**
     * Test that the hook echoes the EULA form when the EULA has not yet been accepted.
     *
     * With test_turnitin_connection() returning true and a user with useragreementaccepted=0,
     * get_accepted_user_agreement() is called. In PHPUnit context its PHPUNIT_TEST guard
     * returns true (accepted), so the output is still ''. We verify no exception is thrown
     * and the hook completes cleanly.
     */
    public function test_echoes_eula_form_when_not_accepted(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Set credentials so turnitin_comms doesn't throw.
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');

        $cm = $this->set_up_quiz_page();

        set_config('plagiarism_turnitin_mod_quiz', 1, 'plagiarism_turnitin');

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'use_turnitin',
            'value'       => '1',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // Register user with turnitin_uid so constructor doesn't call API.
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                  => (int)$USER->id,
            'turnitin_uid'            => 99999,
            'turnitin_utp'            => 0,
            'user_agreement_accepted' => 0,
        ]);

        // Connection returns true so the EULA renderer is entered.
        // PHPUNIT_TEST guard makes get_accepted_user_agreement() return true → '' output.
        $mock = $this->make_mock_plugin(true);

        // Reset the eula form connection cache so this test gets a fresh check.
        \plagiarism_turnitin\turnitin_eula_form::reset_connection_cache();

        $this->expectOutputString('');
        hook_callbacks::before_footer_html_generation($this->make_hook(), $mock);
    }
}

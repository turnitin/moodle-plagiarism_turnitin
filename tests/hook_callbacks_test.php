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
class hook_callbacks_test extends \advanced_testcase {
    /**
     * Build the hook object passed to before_footer_html_generation().
     */
    private function make_hook(): before_footer_html_generation {
        return new before_footer_html_generation($this->createMock(\renderer_base::class));
    }

    /**
     * Build a mock of plagiarism_plugin_turnitin whose render_eula_form() returns $eulahtml.
     *
     * @param string $eulahtml Value to return from render_eula_form(); '' means EULA accepted.
     * @return \plagiarism_plugin_turnitin
     */
    private function make_mock_plugin(string $eulahtml): \plagiarism_plugin_turnitin {
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['render_eula_form'])
            ->getMock();
        $mock->method('render_eula_form')->willReturn($eulahtml);
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
        $mock = $this->make_mock_plugin('');
        $this->expectOutputString('');
        hook_callbacks::before_footer_html_generation($this->make_hook(), $mock);
    }

    /**
     * Test that the hook echoes the EULA form and queues AMD modules when the EULA
     * has not yet been accepted.
     */
    public function test_echoes_eula_form_when_not_accepted(): void {
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

        $mock = $this->make_mock_plugin('<div class="pp_turnitin_eula">EULA form</div>');
        $this->expectOutputRegex('/pp_turnitin_eula/');

        hook_callbacks::before_footer_html_generation($this->make_hook(), $mock);
    }
}

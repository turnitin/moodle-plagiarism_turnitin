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
 * Unit tests for (some of) plagiarism/turnitin/classes/turnitin_view.class.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2026 Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/tests/generator/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/classes/turnitin_view.class.php');

/**
 * Tests for Turnitin view class
 *
 * @package turnitin
 */
final class turnitin_view_class_test extends plagiarism_turnitin_test_lib {

    /**
     * Launch forms are signed locally, so fake credentials are enough and no request reaches Turnitin.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'secret', 'plagiarism_turnitin');
    }

    /**
     * @covers \turnitin_view::output_launch_form
     * @return void
     */
    public function test_output_launch_form_useragreement_includes_return_url(): void {

        $returnurl = 'https://moodle.example.com/plagiarism/turnitin/extras.php?cmd=eula_return';
        $form = \turnitin_view::output_launch_form('useragreement', 0, 1234, 'Learner', '', false, $returnurl);

        $this->assertStringContainsString('name="launch_presentation_return_url"', $form);
        $this->assertStringContainsString('value="' . s($returnurl) . '"', $form);
    }

    /**
     * @covers \turnitin_view::output_launch_form
     * @return void
     */
    public function test_output_launch_form_useragreement_without_return_url(): void {

        $form = \turnitin_view::output_launch_form('useragreement', 0, 1234, 'Learner', '');

        $this->assertStringNotContainsString('launch_presentation_return_url', $form);
    }

    /**
     * @covers \turnitin_view::output_eula_return_script
     * @return void
     */
    public function test_output_eula_return_script(): void {

        $declined = \turnitin_view::output_eula_return_script(false);
        $this->assertStringContainsString('window.parent.postMessage("turnitin_eula_declined", window.location.origin)',
            $declined);
        $this->assertStringNotContainsString('turnitin_eula_accepted', $declined);

        $accepted = \turnitin_view::output_eula_return_script(true);
        $this->assertStringContainsString('window.parent.postMessage("turnitin_eula_accepted", window.location.origin)',
            $accepted);
        $this->assertStringNotContainsString('turnitin_eula_declined', $accepted);
    }
}

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
 * Unit tests for (some of) mod/turnitintooltwo/view.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2017 Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

/**
 * Tests for API comms class
 *
 * @package turnitin
 */
class plagiarism_turnitin_lib_testcase extends advanced_testcase {

    public function test_handle_exceptions() {
        $this->resetAfterTest();

        $plagiarism_turnitin = new plagiarism_plugin_turnitin();
        
        // Check if plugin is configured with no plugin config set.
        $is_plugin_configured = $plagiarism_turnitin->is_plugin_configured();
        $this->assertEquals(false, $is_plugin_configured);
        
        // Check if plugin is configured with only account id set.
        set_config('accountid', '1001', 'turnitintooltwo');
        $is_plugin_configured = $plagiarism_turnitin->is_plugin_configured();
        $this->assertEquals(false, $is_plugin_configured);

        // Check if plugin is configured with account id and apiurl set.
        set_config('apiurl', 'http://www.test.com', 'turnitintooltwo');
        $is_plugin_configured = $plagiarism_turnitin->is_plugin_configured();
        $this->assertEquals(false, $is_plugin_configured);

        // Check if plugin is configured with account id, apiurl and secretkey set.
        set_config('secretkey', 'ABCDEFGH', 'turnitintooltwo');
        $is_plugin_configured = $plagiarism_turnitin->is_plugin_configured();
        $this->assertEquals(true, $is_plugin_configured);
    }
}